<?php

namespace Gwinto\ReavaPay\Observers;

use App\Models\WalletTransaction;
use Gwinto\ReavaPay\Services\WalletSyncService;
use Illuminate\Support\Facades\Log;

class WalletTransactionObserver
{
    /**
     * Handle the WalletTransaction "created" event.
     * Sync completed wallet transactions to Reava Pay.
     */
    public function created(WalletTransaction $transaction): void
    {
        // Only sync completed transactions that didn't originate from Reava Pay
        if ($transaction->status !== 'completed') {
            return;
        }

        // Skip if this transaction was created by Reava Pay sync (prevent loops)
        $metadata = $transaction->metadata ?? [];
        if (isset($metadata['sync_source']) && $metadata['sync_source'] === 'reava_pay') {
            return;
        }

        // Skip Reava Pay related transactions (already tracked)
        if ($transaction->related_type === 'Gwinto\ReavaPay\Models\ReavaPayTransaction') {
            return;
        }

        try {
            $syncService = app(WalletSyncService::class);
            $syncService->syncToReavaPay($transaction);
        } catch (\Exception $e) {
            // Non-critical - log but don't fail
            Log::warning('Reava Pay sync from observer failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
