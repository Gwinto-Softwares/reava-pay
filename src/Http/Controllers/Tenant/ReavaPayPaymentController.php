<?php

namespace Gwinto\ReavaPay\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant;
use Gwinto\ReavaPay\Jobs\ProcessReavaPayCollection;
use Gwinto\ReavaPay\Models\ReavaPaySetting;
use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Gwinto\ReavaPay\Services\ReavaPayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReavaPayPaymentController extends Controller
{
    /**
     * Get current tenant.
     */
    protected function getTenant(): Tenant
    {
        return auth('tenant')->user();
    }

    /**
     * Show invoice payment page with Reava Pay options.
     */
    public function payInvoice(int $invoiceId)
    {
        $tenant = $this->getTenant();
        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->where('id', $invoiceId)
            ->firstOrFail();

        if ($invoice->isPaid()) {
            return redirect()->route('tenant.invoices.index')
                ->with('info', 'This invoice has already been paid.');
        }

        $company = $invoice->company;
        $settings = ReavaPaySetting::effectiveForCompany($company->id);
        $channels = $settings ? $settings->getEnabledChannels() : [];
        $remainingAmount = $invoice->remainingAmount();

        return view('reava-pay::tenant.pay-invoice', compact(
            'tenant', 'invoice', 'company', 'settings', 'channels', 'remainingAmount'
        ));
    }

    /**
     * Initiate invoice payment via Reava Pay.
     */
    public function processInvoicePayment(Request $request, int $invoiceId)
    {
        $tenant = $this->getTenant();
        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->where('id', $invoiceId)
            ->firstOrFail();

        if ($invoice->isPaid()) {
            return back()->with('error', 'This invoice has already been paid.');
        }

        $request->validate([
            'channel' => 'required|in:mpesa,card,bank_transfer',
            'phone' => 'required_if:channel,mpesa|nullable|string',
            'email' => 'required_if:channel,card|nullable|email',
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = min($request->amount, $invoice->remainingAmount());

        $company = $invoice->company;
        $settings = ReavaPaySetting::effectiveForCompany($company->id);

        if (!$settings || !$settings->is_active) {
            return back()->with('error', 'Reava Pay is not available for this company.');
        }

        // Validate amount against limits
        if ($amount < $settings->min_transaction_amount) {
            return back()->with('error', 'Minimum payment amount is ' . $settings->default_currency . ' ' . number_format($settings->min_transaction_amount, 2));
        }
        if ($amount > $settings->max_transaction_amount) {
            return back()->with('error', 'Maximum payment amount is ' . $settings->default_currency . ' ' . number_format($settings->max_transaction_amount, 2));
        }

        $callbackUrl = route('reava-pay.payment.status', ['reference' => 'PLACEHOLDER']);

        // Create local transaction record
        $transaction = ReavaPayTransaction::create([
            'company_id' => $company->id,
            'payer_type' => Tenant::class,
            'payer_id' => $tenant->id,
            'payee_type' => \App\Models\Company::class,
            'payee_id' => $company->id,
            'type' => ReavaPayTransaction::TYPE_INVOICE_PAYMENT,
            'channel' => $request->channel,
            'amount' => $amount,
            'charge_amount' => 0,
            'net_amount' => $amount,
            'currency' => $settings->default_currency,
            'phone' => $request->phone,
            'email' => $request->email ?? $tenant->email,
            'account_reference' => $invoice->invoice_number,
            'description' => 'Payment for invoice ' . $invoice->invoice_number,
            'callback_url' => $callbackUrl,
            'invoice_id' => $invoice->id,
            'idempotency_key' => 'INV-' . $invoice->id . '-' . Str::random(8),
            'metadata' => [
                'invoice_number' => $invoice->invoice_number,
                'tenant_name' => $tenant->name,
                'company_name' => $company->name,
            ],
        ]);

        try {
            $gateway = ReavaPayGateway::forCompany($company->id);

            $response = $gateway->initiateInvoicePayment([
                'amount' => $amount,
                'currency' => $settings->default_currency,
                'phone' => $request->phone,
                'email' => $request->email ?? $tenant->email,
                'channel' => $request->channel,
                'invoice_number' => $invoice->invoice_number,
                'description' => 'Gwinto Invoice Payment: ' . $invoice->invoice_number,
                'callback_url' => route('reava-pay.webhook'),
                'metadata' => [
                    'gwinto_reference' => $transaction->gwinto_reference,
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $tenant->id,
                    'company_id' => $company->id,
                ],
            ]);

            $transaction->update([
                'status' => ReavaPayTransaction::STATUS_PROCESSING,
                'reava_reference' => $response['data']['reference'] ?? null,
                'authorization_url' => $response['data']['authorization_url'] ?? null,
                'reava_response' => $response,
            ]);

            // For card payments, redirect to authorization URL
            if ($request->channel === 'card' && !empty($response['data']['authorization_url'])) {
                return redirect()->away($response['data']['authorization_url']);
            }

            // For M-Pesa, show status page
            return redirect()->route('reava-pay.payment.status', [
                'reference' => $transaction->gwinto_reference,
            ])->with('success', 'Payment initiated. Please check your phone for the M-Pesa prompt.');

        } catch (\Exception $e) {
            $transaction->markAsFailed($e->getMessage());
            return back()->with('error', 'Payment initiation failed: ' . $e->getMessage());
        }
    }

    /**
     * Show wallet top-up page.
     */
    public function walletTopUp()
    {
        $tenant = $this->getTenant();
        $wallet = $tenant->getOrCreateWallet();
        $company = $tenant->company;

        $settings = ReavaPaySetting::effectiveForCompany($company->id);
        $channels = $settings ? $settings->getEnabledChannels() : [];

        $recentTopUps = ReavaPayTransaction::forPayer(Tenant::class, $tenant->id)
            ->ofType(ReavaPayTransaction::TYPE_WALLET_TOPUP)
            ->latest()
            ->take(5)
            ->get();

        return view('reava-pay::tenant.wallet-topup', compact(
            'tenant', 'wallet', 'company', 'settings', 'channels', 'recentTopUps'
        ));
    }

    /**
     * Process wallet top-up via Reava Pay.
     */
    public function processWalletTopUp(Request $request)
    {
        $tenant = $this->getTenant();
        $company = $tenant->company;

        $request->validate([
            'channel' => 'required|in:mpesa,card,bank_transfer',
            'phone' => 'required_if:channel,mpesa|nullable|string',
            'email' => 'required_if:channel,card|nullable|email',
            'amount' => 'required|numeric|min:10',
        ]);

        $settings = ReavaPaySetting::effectiveForCompany($company->id);

        if (!$settings || !$settings->is_active) {
            return back()->with('error', 'Reava Pay is not currently available.');
        }

        $amount = $request->amount;

        if ($amount < $settings->min_transaction_amount) {
            return back()->with('error', 'Minimum top-up amount is ' . $settings->default_currency . ' ' . number_format($settings->min_transaction_amount, 2));
        }
        if ($amount > $settings->max_transaction_amount) {
            return back()->with('error', 'Maximum top-up amount is ' . $settings->default_currency . ' ' . number_format($settings->max_transaction_amount, 2));
        }

        $transaction = ReavaPayTransaction::create([
            'company_id' => $company->id,
            'payer_type' => Tenant::class,
            'payer_id' => $tenant->id,
            'type' => ReavaPayTransaction::TYPE_WALLET_TOPUP,
            'channel' => $request->channel,
            'amount' => $amount,
            'charge_amount' => 0,
            'net_amount' => $amount,
            'currency' => $settings->default_currency,
            'phone' => $request->phone,
            'email' => $request->email ?? $tenant->email,
            'account_reference' => 'TOPUP-' . $tenant->id,
            'description' => 'Wallet top-up for ' . $tenant->name,
            'idempotency_key' => 'TOPUP-' . $tenant->id . '-' . Str::random(8),
            'metadata' => [
                'tenant_name' => $tenant->name,
                'wallet_number' => $tenant->wallet?->wallet_number,
            ],
        ]);

        try {
            $gateway = ReavaPayGateway::forCompany($company->id);

            $response = $gateway->initiateWalletTopUp([
                'amount' => $amount,
                'currency' => $settings->default_currency,
                'phone' => $request->phone,
                'email' => $request->email ?? $tenant->email,
                'channel' => $request->channel,
                'account_reference' => $transaction->gwinto_reference,
                'description' => 'Gwinto Wallet Top-Up: ' . $tenant->name,
                'callback_url' => route('reava-pay.webhook'),
                'metadata' => [
                    'gwinto_reference' => $transaction->gwinto_reference,
                    'tenant_id' => $tenant->id,
                    'type' => 'wallet_topup',
                ],
            ]);

            $transaction->update([
                'status' => ReavaPayTransaction::STATUS_PROCESSING,
                'reava_reference' => $response['data']['reference'] ?? null,
                'authorization_url' => $response['data']['authorization_url'] ?? null,
                'reava_response' => $response,
            ]);

            if ($request->channel === 'card' && !empty($response['data']['authorization_url'])) {
                return redirect()->away($response['data']['authorization_url']);
            }

            return redirect()->route('reava-pay.payment.status', [
                'reference' => $transaction->gwinto_reference,
            ])->with('success', 'Top-up initiated. Please check your phone for the M-Pesa prompt.');

        } catch (\Exception $e) {
            $transaction->markAsFailed($e->getMessage());
            return back()->with('error', 'Top-up initiation failed: ' . $e->getMessage());
        }
    }

    /**
     * Show payment status page.
     */
    public function paymentStatus(string $reference)
    {
        $tenant = $this->getTenant();
        $transaction = ReavaPayTransaction::where('gwinto_reference', $reference)
            ->where('payer_type', Tenant::class)
            ->where('payer_id', $tenant->id)
            ->firstOrFail();

        return view('reava-pay::tenant.payment-status', compact('tenant', 'transaction'));
    }

    /**
     * Check payment status via AJAX.
     */
    public function checkStatus(string $reference)
    {
        $tenant = $this->getTenant();
        $transaction = ReavaPayTransaction::where('gwinto_reference', $reference)
            ->where('payer_type', Tenant::class)
            ->where('payer_id', $tenant->id)
            ->firstOrFail();

        // If still processing, poll Reava Pay
        if ($transaction->isProcessing() && $transaction->reava_reference) {
            try {
                $gateway = $transaction->company_id
                    ? ReavaPayGateway::forCompany($transaction->company_id)
                    : ReavaPayGateway::platform();

                $result = $gateway->checkTransactionStatus($transaction->reava_reference);

                if (isset($result['data']['status'])) {
                    $apiStatus = $result['data']['status'];
                    if ($apiStatus === 'completed' && !$transaction->isCompleted()) {
                        // Will be handled by webhook, but update local status
                        $transaction->update(['status' => 'processing']);
                    }
                }
            } catch (\Exception $e) {
                // Silently ignore polling errors
            }
        }

        return response()->json([
            'status' => $transaction->status,
            'formatted_amount' => $transaction->formatted_amount,
            'reference' => $transaction->gwinto_reference,
            'reava_reference' => $transaction->reava_reference,
            'provider_reference' => $transaction->provider_reference,
            'completed_at' => $transaction->completed_at?->format('M d, Y H:i'),
            'failure_reason' => $transaction->failure_reason,
        ]);
    }

    /**
     * Tenant's Reava Pay transaction history.
     */
    public function history(Request $request)
    {
        $tenant = $this->getTenant();

        $query = ReavaPayTransaction::forPayer(Tenant::class, $tenant->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->latest()->paginate(15);

        $stats = [
            'total_paid' => ReavaPayTransaction::forPayer(Tenant::class, $tenant->id)
                ->completed()->sum('amount'),
            'this_month' => ReavaPayTransaction::forPayer(Tenant::class, $tenant->id)
                ->completed()
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'pending' => ReavaPayTransaction::forPayer(Tenant::class, $tenant->id)
                ->pending()->count(),
        ];

        return view('reava-pay::tenant.history', compact('tenant', 'transactions', 'stats'));
    }
}
