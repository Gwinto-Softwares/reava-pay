<?php

namespace ReavaPay\Gwinto\Services;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReavaPay\Gwinto\Facades\Gwinto;

class WalletSyncService
{
    /**
     * Sync a Gwinto company charge / platform billing invoice to Reava Pay.
     *
     * Call this when admin creates a platform charge against a Gwinto company.
     * Creates the invoice on Reava Pay and stores the cross-reference ID.
     *
     * @param  array{
     *     invoice_id: int,
     *     customer_name: string,
     *     customer_email?: string,
     *     customer_phone?: string,
     *     currency_code?: string,
     *     issue_date: string,
     *     due_date?: string,
     *     items: array<array{description: string, quantity: float, unit_price: float, tax_rate?: float}>,
     *     notes?: string,
     *     reference?: string,
     *     allow_partial_payments?: bool,
     *     minimum_payment_amount?: float,
     *     gwinto_company_id?: string,
     * }  $invoiceData
     */
    public function syncToReavaPay(array $invoiceData): bool
    {
        $localInvoiceId = $invoiceData['invoice_id'] ?? null;

        try {
            // Skip if already synced from Reava Pay (prevent loops)
            $metadata = $invoiceData['metadata'] ?? [];
            if (isset($metadata['sync_source']) && $metadata['sync_source'] === 'reava_pay') {
                return true;
            }

            $response = Gwinto::invoices()->create([
                'customer_name' => $invoiceData['customer_name'],
                'customer_email' => $invoiceData['customer_email'] ?? null,
                'customer_phone' => $invoiceData['customer_phone'] ?? null,
                'currency_code' => $invoiceData['currency_code'] ?? config('gwinto.currency', 'KES'),
                'issue_date' => $invoiceData['issue_date'],
                'due_date' => $invoiceData['due_date'] ?? null,
                'items' => $invoiceData['items'],
                'notes' => $invoiceData['notes'] ?? null,
                'reference' => $invoiceData['reference'] ?? null,
                'allow_partial_payments' => $invoiceData['allow_partial_payments'] ?? false,
                'minimum_payment_amount' => $invoiceData['minimum_payment_amount'] ?? null,
                'metadata' => [
                    'sync_source' => 'gwinto',
                    'gwinto_company_id' => $invoiceData['gwinto_company_id'] ?? null,
                    'local_invoice_id' => $localInvoiceId,
                ],
            ]);

            $remoteInvoiceId = $response['data']['id'] ?? $response['data']['uuid'] ?? null;

            if ($remoteInvoiceId && $localInvoiceId) {
                Invoice::where('id', $localInvoiceId)->update([
                    'gwinto_invoice_id' => $remoteInvoiceId,
                    'gwinto_sync_status' => 'synced',
                    'gwinto_synced_at' => now(),
                ]);
            }

            Log::info('Gwinto invoice synced to Reava Pay', [
                'local_invoice_id' => $localInvoiceId,
                'remote_invoice_id' => $remoteInvoiceId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('Gwinto to Reava Pay invoice sync failed', [
                'local_invoice_id' => $localInvoiceId,
                'error' => $e->getMessage(),
            ]);

            if ($localInvoiceId) {
                Invoice::where('id', $localInvoiceId)->update([
                    'gwinto_sync_status' => 'failed',
                ]);
            }

            return false;
        }
    }

    /**
     * Handle a Reava Pay webhook payload and sync the payment back to the local invoice.
     *
     * Supported events: transaction.completed, payment.completed, invoice.paid, invoice.payment.received
     *
     * @param  array{
     *     event?: string,
     *     data: array{
     *         reference?: string,
     *         amount?: float,
     *         currency_code?: string,
     *         metadata?: array,
     *         provider_reference?: string,
     *         payer_phone?: string,
     *         payer_email?: string,
     *         completed_at?: string,
     *     }
     * }  $webhookData
     */
    public function syncFromReavaPay(array $webhookData): bool
    {
        try {
            $event = $webhookData['event'] ?? 'unknown';
            $data = $webhookData['data'] ?? $webhookData;
            $metadata = $data['metadata'] ?? [];

            // Resolve which local invoice this relates to
            $invoiceId = $metadata['invoice_id'] ?? null;
            $gwintoInvoiceId = $metadata['gwinto_invoice_id'] ?? $data['invoice_id'] ?? null;

            $invoice = null;

            if ($invoiceId) {
                $invoice = Invoice::find($invoiceId);
            }

            if (! $invoice && $gwintoInvoiceId) {
                $invoice = Invoice::where('gwinto_invoice_id', $gwintoInvoiceId)->first();
            }

            if (! $invoice) {
                Log::info('Gwinto webhook: no matching local invoice', [
                    'event' => $event,
                    'invoice_id' => $invoiceId,
                    'gwinto_invoice_id' => $gwintoInvoiceId,
                ]);

                return true; // Not our invoice — not an error
            }

            if ($invoice->isPaid() && in_array($event, ['invoice.paid', 'transaction.completed', 'payment.completed'])) {
                return true; // Already fully paid, idempotent
            }

            $paidAmount = (float) ($data['amount'] ?? 0);
            if ($paidAmount <= 0) {
                return true;
            }

            DB::transaction(function () use ($invoice, $data, $paidAmount, $event) {
                $newAmountPaid = round((float) $invoice->amount_paid + $paidAmount, 2);
                $newBalanceDue = round(max(0, (float) $invoice->total_amount - $newAmountPaid), 2);
                $isFullyPaid = $newBalanceDue <= 0;

                // Record the payment
                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $paidAmount,
                    'currency_code' => $data['currency_code'] ?? $invoice->currency_code,
                    'method' => $data['channel'] ?? $data['payment_method'] ?? 'api',
                    'reference' => $data['reference'] ?? null,
                    'provider_reference' => $data['provider_reference'] ?? null,
                    'payer_phone' => $data['payer_phone'] ?? null,
                    'payer_email' => $data['payer_email'] ?? null,
                    'is_partial' => ! $isFullyPaid,
                    'notes' => "Payment received via Reava Pay webhook ({$event})",
                    'paid_at' => $data['completed_at'] ?? now(),
                ]);

                $invoice->update([
                    'amount_paid' => $newAmountPaid,
                    'balance_due' => $newBalanceDue,
                    'status' => $isFullyPaid ? 'paid' : 'partially_paid',
                    'paid_at' => $isFullyPaid ? ($data['completed_at'] ?? now()) : $invoice->paid_at,
                    'gwinto_sync_status' => 'synced',
                    'gwinto_synced_at' => now(),
                ]);

                $invoice->logActivity(
                    action: $isFullyPaid ? 'paid' : 'partial_payment',
                    description: $isFullyPaid
                        ? "Invoice fully paid via Reava Pay. Amount: {$invoice->currency_code} ".number_format($paidAmount, 2)
                        : "Partial payment received via Reava Pay. Amount: {$invoice->currency_code} ".number_format($paidAmount, 2).'. Balance due: '.number_format($newBalanceDue, 2),
                    channel: 'webhook',
                    actorType: 'system',
                    actorName: 'Reava Pay Webhook',
                    metadata: ['event' => $event, 'reference' => $data['reference'] ?? null],
                );
            });

            Log::info('Reava Pay payment synced to local invoice', [
                'invoice_id' => $invoice->id,
                'amount' => $paidAmount,
                'event' => $event,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Reava Pay to Gwinto sync failed', [
                'error' => $e->getMessage(),
                'event' => $webhookData['event'] ?? 'unknown',
            ]);

            return false;
        }
    }

    /**
     * Reconcile balances between Gwinto wallet and Reava Pay float account.
     */
    public function reconcile(float $localBalance): array
    {
        try {
            $remote = Gwinto::balance();

            return [
                'synced' => abs($localBalance - $remote->available) < 1,
                'local_balance' => $localBalance,
                'remote_balance' => $remote->available,
                'difference' => abs($localBalance - $remote->available),
            ];
        } catch (\Exception $e) {
            return [
                'synced' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
