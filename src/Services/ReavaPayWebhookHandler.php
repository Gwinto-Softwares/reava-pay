<?php

namespace Gwinto\ReavaPay\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\WalletService;
use Gwinto\ReavaPay\Events\ReavaPayPaymentCompleted;
use Gwinto\ReavaPay\Events\ReavaPayPaymentFailed;
use Gwinto\ReavaPay\Models\ReavaPaySetting;
use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ReavaPayWebhookHandler
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle incoming webhook from Reava Pay.
     */
    public function handle(array $payload): array
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;

        Log::info('Reava Pay Webhook Received', [
            'event' => $event,
            'reference' => $reference,
        ]);

        if (!$reference) {
            return ['success' => false, 'message' => 'Missing reference'];
        }

        // Find local transaction by Reava reference
        $transaction = ReavaPayTransaction::where('reava_reference', $reference)->first();

        if (!$transaction) {
            Log::warning('Reava Pay webhook: Transaction not found', ['reference' => $reference]);
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        // Store raw webhook payload
        $transaction->update(['webhook_payload' => $payload]);

        return match ($event) {
            'transaction.completed' => $this->handleCompleted($transaction, $data),
            'transaction.failed' => $this->handleFailed($transaction, $data),
            'transaction.reversed' => $this->handleReversed($transaction, $data),
            default => ['success' => true, 'message' => 'Event acknowledged'],
        };
    }

    /**
     * Handle successful payment.
     */
    protected function handleCompleted(ReavaPayTransaction $transaction, array $data): array
    {
        if ($transaction->isCompleted()) {
            return ['success' => true, 'message' => 'Already processed'];
        }

        DB::beginTransaction();
        try {
            $transaction->markAsCompleted([
                'provider_reference' => $data['provider_reference'] ?? $transaction->provider_reference,
                'reava_response' => $data,
            ]);

            // Process based on transaction type
            match ($transaction->type) {
                ReavaPayTransaction::TYPE_WALLET_TOPUP => $this->processWalletTopUp($transaction),
                ReavaPayTransaction::TYPE_INVOICE_PAYMENT => $this->processInvoicePayment($transaction),
                ReavaPayTransaction::TYPE_COLLECTION => $this->processCollection($transaction),
                default => null,
            };

            DB::commit();

            event(new ReavaPayPaymentCompleted($transaction));

            Log::info('Reava Pay payment completed', [
                'reference' => $transaction->gwinto_reference,
                'amount' => $transaction->amount,
            ]);

            return ['success' => true, 'message' => 'Payment processed'];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Reava Pay webhook processing error', [
                'reference' => $transaction->gwinto_reference,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle failed payment.
     */
    protected function handleFailed(ReavaPayTransaction $transaction, array $data): array
    {
        $reason = $data['failure_reason'] ?? $data['message'] ?? 'Payment failed';

        $transaction->markAsFailed($reason, [
            'reava_response' => $data,
        ]);

        event(new ReavaPayPaymentFailed($transaction));

        return ['success' => true, 'message' => 'Failure recorded'];
    }

    /**
     * Handle reversed payment.
     */
    protected function handleReversed(ReavaPayTransaction $transaction, array $data): array
    {
        $transaction->update([
            'status' => ReavaPayTransaction::STATUS_REVERSED,
            'reava_response' => $data,
        ]);

        // Reverse wallet credit if applicable
        if ($transaction->wallet_transaction_id) {
            $wallet = $this->findPayerWallet($transaction);
            if ($wallet) {
                $this->walletService->debit($wallet, $transaction->amount, [
                    'category' => 'reversal',
                    'description' => 'Reava Pay payment reversal: ' . $transaction->gwinto_reference,
                    'related_type' => ReavaPayTransaction::class,
                    'related_id' => $transaction->id,
                ]);
            }
        }

        return ['success' => true, 'message' => 'Reversal processed'];
    }

    /**
     * Process wallet top-up after successful payment.
     */
    protected function processWalletTopUp(ReavaPayTransaction $transaction): void
    {
        $wallet = $this->findPayerWallet($transaction);

        if (!$wallet) {
            throw new Exception('Payer wallet not found for top-up');
        }

        $settings = $this->getSettingsForTransaction($transaction);
        if (!$settings || !$settings->auto_credit_wallet) {
            return;
        }

        $result = $this->walletService->credit($wallet, $transaction->amount, [
            'category' => 'wallet_topup',
            'description' => 'Wallet top-up via Reava Pay (' . $transaction->channel_label . ')',
            'payment_method' => $transaction->channel,
            'external_reference' => $transaction->reava_reference,
            'related_type' => ReavaPayTransaction::class,
            'related_id' => $transaction->id,
            'metadata' => [
                'reava_reference' => $transaction->reava_reference,
                'provider_reference' => $transaction->provider_reference,
                'channel' => $transaction->channel,
            ],
        ]);

        if ($result['success']) {
            $transaction->update([
                'wallet_transaction_id' => $result['transaction']->id,
            ]);
        }
    }

    /**
     * Process invoice payment after successful payment.
     */
    protected function processInvoicePayment(ReavaPayTransaction $transaction): void
    {
        if (!$transaction->invoice_id) {
            return;
        }

        $invoice = Invoice::find($transaction->invoice_id);
        if (!$invoice) {
            return;
        }

        // Create a Payment record
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $transaction->payer_type === 'App\Models\Tenant' ? $transaction->payer_id : null,
            'company_id' => $transaction->company_id,
            'amount' => $transaction->amount,
            'payment_date' => now(),
            'payment_method' => $transaction->channel === 'mpesa' ? 'mpesa' : ($transaction->channel === 'card' ? 'card' : 'bank_transfer'),
            'transaction_reference' => $transaction->reava_reference,
            'status' => 'completed',
            'notes' => 'Paid via Reava Pay - ' . $transaction->channel_label,
        ]);

        $transaction->update(['payment_id' => $payment->id]);

        // Credit company wallet
        $settings = $this->getSettingsForTransaction($transaction);
        if ($settings && $settings->auto_credit_wallet && $transaction->company_id) {
            $company = \App\Models\Company::find($transaction->company_id);
            if ($company && $company->wallet && $company->paidToWallet()) {
                $result = $this->walletService->credit($company->wallet, $transaction->amount, [
                    'category' => 'rent_payment',
                    'description' => 'Invoice payment via Reava Pay: ' . $invoice->invoice_number,
                    'payment_method' => $transaction->channel,
                    'external_reference' => $transaction->reava_reference,
                    'related_type' => Payment::class,
                    'related_id' => $payment->id,
                ]);

                if ($result['success']) {
                    $transaction->update(['wallet_transaction_id' => $result['transaction']->id]);
                }
            }
        }
    }

    /**
     * Process a generic collection.
     */
    protected function processCollection(ReavaPayTransaction $transaction): void
    {
        $wallet = $this->findPayerWallet($transaction);

        if ($wallet) {
            $settings = $this->getSettingsForTransaction($transaction);
            if ($settings && $settings->auto_credit_wallet) {
                $result = $this->walletService->credit($wallet, $transaction->amount, [
                    'category' => 'wallet_topup',
                    'description' => 'Reava Pay collection: ' . $transaction->gwinto_reference,
                    'payment_method' => $transaction->channel,
                    'external_reference' => $transaction->reava_reference,
                    'related_type' => ReavaPayTransaction::class,
                    'related_id' => $transaction->id,
                ]);

                if ($result['success']) {
                    $transaction->update(['wallet_transaction_id' => $result['transaction']->id]);
                }
            }
        }
    }

    /**
     * Find the wallet for the payer.
     */
    protected function findPayerWallet(ReavaPayTransaction $transaction)
    {
        $payer = $transaction->payer;
        return $payer ? $payer->wallet : null;
    }

    /**
     * Get Reava Pay settings for a transaction.
     */
    protected function getSettingsForTransaction(ReavaPayTransaction $transaction): ?ReavaPaySetting
    {
        if ($transaction->company_id) {
            return ReavaPaySetting::effectiveForCompany($transaction->company_id);
        }

        return ReavaPaySetting::platform();
    }
}
