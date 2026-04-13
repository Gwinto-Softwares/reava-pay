<?php

namespace ReavaPay\Gwinto\Observers;

use ReavaPay\Gwinto\Services\WalletSyncService;
use Illuminate\Support\Facades\Log;

class WalletTransactionObserver
{
    public function created($transaction): void
    {
        if ($transaction->status !== 'completed') {
            return;
        }

        $metadata = $transaction->metadata ?? [];
        if (isset($metadata['sync_source']) && $metadata['sync_source'] === 'reava_pay') {
            return;
        }

        try {
            app(WalletSyncService::class)->syncToReavaPay($transaction->toArray());
        } catch (\Exception $e) {
            Log::warning('Gwinto wallet sync failed (non-critical)', ['error' => $e->getMessage()]);
        }
    }
}
