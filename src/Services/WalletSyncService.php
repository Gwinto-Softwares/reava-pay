<?php

namespace Gwinto\ReavaPay\Services;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Gwinto\ReavaPay\Models\ReavaPaySetting;
use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class WalletSyncService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Sync a Gwinto wallet transaction to Reava Pay.
     * Called when a local wallet transaction occurs (e.g., rent payment, wallet transfer).
     */
    public function syncToReavaPay(WalletTransaction $transaction): array
    {
        $wallet = $transaction->wallet;
        if (!$wallet) {
            return ['success' => false, 'message' => 'Wallet not found'];
        }

        // Determine company context
        $companyId = $this->resolveCompanyId($wallet);
        if (!$companyId) {
            return ['success' => false, 'message' => 'No company context for sync'];
        }

        $settings = ReavaPaySetting::effectiveForCompany($companyId);
        if (!$settings || !$settings->is_active) {
            return ['success' => true, 'message' => 'Reava Pay not active, skip sync'];
        }

        try {
            $gateway = ReavaPayGateway::forCompany($companyId);

            // Create a corresponding transaction record on Reava Pay
            $type = $transaction->type === 'credit' ? 'collection' : 'payout';
            $channel = $this->mapPaymentMethod($transaction->payment_method);

            // Log the sync event
            $rpTransaction = ReavaPayTransaction::create([
                'company_id' => $companyId,
                'payer_type' => $wallet->holder_type,
                'payer_id' => $wallet->holder_id,
                'type' => $type,
                'channel' => $channel,
                'amount' => $transaction->amount,
                'charge_amount' => 0,
                'net_amount' => $transaction->amount,
                'currency' => $wallet->currency ?? 'KES',
                'status' => ReavaPayTransaction::STATUS_COMPLETED,
                'gwinto_reference' => 'SYNC-' . $transaction->id . '-' . now()->timestamp,
                'wallet_transaction_id' => $transaction->id,
                'description' => 'Synced from Gwinto: ' . $transaction->description,
                'completed_at' => $transaction->completed_at ?? now(),
                'metadata' => [
                    'sync_source' => 'gwinto',
                    'original_category' => $transaction->category,
                    'original_type' => $transaction->type,
                    'wallet_number' => $wallet->wallet_number,
                ],
            ]);

            Log::info('Wallet transaction synced to Reava Pay', [
                'wallet_transaction_id' => $transaction->id,
                'reava_pay_transaction_id' => $rpTransaction->id,
            ]);

            return [
                'success' => true,
                'message' => 'Synced to Reava Pay',
                'rp_transaction' => $rpTransaction,
            ];
        } catch (Exception $e) {
            Log::warning('Reava Pay sync failed (non-critical)', [
                'wallet_transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync a Reava Pay transaction to Gwinto wallet.
     * Called from webhook handler when Reava Pay reports a completed transaction.
     */
    public function syncFromReavaPay(ReavaPayTransaction $rpTransaction): array
    {
        if ($rpTransaction->wallet_transaction_id) {
            return ['success' => true, 'message' => 'Already synced'];
        }

        $wallet = $this->resolveWallet($rpTransaction);
        if (!$wallet) {
            return ['success' => false, 'message' => 'Target wallet not found'];
        }

        DB::beginTransaction();
        try {
            // Credit or debit based on transaction type
            if (in_array($rpTransaction->type, ['collection', 'wallet_topup', 'invoice_payment'])) {
                $result = $this->walletService->credit($wallet, $rpTransaction->amount, [
                    'category' => $this->mapToGwintoCategory($rpTransaction),
                    'description' => 'Reava Pay: ' . ($rpTransaction->description ?: $rpTransaction->type_label),
                    'payment_method' => $rpTransaction->channel,
                    'external_reference' => $rpTransaction->reava_reference,
                    'related_type' => ReavaPayTransaction::class,
                    'related_id' => $rpTransaction->id,
                    'metadata' => [
                        'sync_source' => 'reava_pay',
                        'reava_reference' => $rpTransaction->reava_reference,
                        'provider_reference' => $rpTransaction->provider_reference,
                        'channel' => $rpTransaction->channel,
                    ],
                ]);
            } elseif (in_array($rpTransaction->type, ['payout', 'settlement'])) {
                $result = $this->walletService->debit($wallet, $rpTransaction->amount, [
                    'category' => 'withdrawal',
                    'description' => 'Reava Pay payout: ' . $rpTransaction->gwinto_reference,
                    'payment_method' => $rpTransaction->channel,
                    'external_reference' => $rpTransaction->reava_reference,
                    'related_type' => ReavaPayTransaction::class,
                    'related_id' => $rpTransaction->id,
                ]);
            } else {
                DB::rollBack();
                return ['success' => false, 'message' => 'Unknown transaction type'];
            }

            if ($result['success']) {
                $rpTransaction->update([
                    'wallet_transaction_id' => $result['transaction']->id,
                ]);

                DB::commit();

                Log::info('Reava Pay transaction synced to Gwinto wallet', [
                    'rp_transaction_id' => $rpTransaction->id,
                    'wallet_transaction_id' => $result['transaction']->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Synced to Gwinto wallet',
                    'wallet_transaction' => $result['transaction'],
                ];
            }

            DB::rollBack();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Reava Pay to Gwinto sync failed', [
                'rp_transaction_id' => $rpTransaction->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Perform a full balance reconciliation between Gwinto and Reava Pay.
     */
    public function reconcile(int $companyId): array
    {
        $company = Company::find($companyId);
        if (!$company) {
            return ['success' => false, 'message' => 'Company not found'];
        }

        try {
            $gateway = ReavaPayGateway::forCompany($companyId);
            $floatAccounts = $gateway->getFloatAccounts();

            $gwintoBalance = $company->wallet?->balance ?? 0;
            $reavaBalance = $floatAccounts['data'][0]['available_balance'] ?? 0;
            $difference = abs($gwintoBalance - $reavaBalance);

            $settings = ReavaPaySetting::forCompany($companyId);
            if ($settings) {
                $settings->update([
                    'last_synced_at' => now(),
                    'metadata' => array_merge($settings->metadata ?? [], [
                        'last_reconciliation' => [
                            'gwinto_balance' => $gwintoBalance,
                            'reava_balance' => $reavaBalance,
                            'difference' => $difference,
                            'timestamp' => now()->toIso8601String(),
                        ],
                    ]),
                ]);
            }

            return [
                'success' => true,
                'gwinto_balance' => $gwintoBalance,
                'reava_balance' => $reavaBalance,
                'difference' => $difference,
                'in_sync' => $difference < 1, // Allow < 1 KES tolerance
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resolve the company ID from a wallet.
     */
    protected function resolveCompanyId(Wallet $wallet): ?int
    {
        if ($wallet->holder_type === Company::class) {
            return $wallet->holder_id;
        }

        if ($wallet->holder_type === Tenant::class) {
            $tenant = Tenant::find($wallet->holder_id);
            return $tenant?->company_id;
        }

        return null;
    }

    /**
     * Resolve the target wallet for a Reava Pay transaction.
     */
    protected function resolveWallet(ReavaPayTransaction $transaction): ?Wallet
    {
        if ($transaction->payer_type && $transaction->payer_id) {
            $payer = app($transaction->payer_type)->find($transaction->payer_id);
            return $payer?->wallet;
        }

        if ($transaction->company_id) {
            $company = Company::find($transaction->company_id);
            return $company?->wallet;
        }

        return null;
    }

    /**
     * Map Gwinto payment method to Reava Pay channel.
     */
    protected function mapPaymentMethod(?string $method): string
    {
        return match ($method) {
            'mpesa', 'mobile_money' => 'mpesa',
            'card', 'visa', 'mastercard' => 'card',
            'bank_transfer', 'bank' => 'bank_transfer',
            default => 'mpesa',
        };
    }

    /**
     * Map Reava Pay transaction to Gwinto wallet category.
     */
    protected function mapToGwintoCategory(ReavaPayTransaction $transaction): string
    {
        return match ($transaction->type) {
            'wallet_topup' => WalletTransaction::CATEGORY_WALLET_TOPUP,
            'invoice_payment' => WalletTransaction::CATEGORY_RENT_PAYMENT,
            'collection' => WalletTransaction::CATEGORY_WALLET_TOPUP,
            'payout' => WalletTransaction::CATEGORY_WITHDRAWAL,
            'settlement' => WalletTransaction::CATEGORY_WITHDRAWAL,
            default => WalletTransaction::CATEGORY_WALLET_TOPUP,
        };
    }
}
