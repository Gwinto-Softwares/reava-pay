<?php

namespace Gwinto\ReavaPay\Console;

use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Gwinto\ReavaPay\Services\ReavaPayGateway;
use Gwinto\ReavaPay\Services\ReavaPayWebhookHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTransactionsCommand extends Command
{
    protected $signature = 'reava-pay:sync {--company= : Sync for a specific company ID}';
    protected $description = 'Poll Reava Pay API for stale/pending transaction updates';

    public function handle(ReavaPayWebhookHandler $handler): int
    {
        $companyId = $this->option('company');

        $query = ReavaPayTransaction::whereIn('status', [
            ReavaPayTransaction::STATUS_PENDING,
            ReavaPayTransaction::STATUS_PROCESSING,
        ])->where('created_at', '>=', now()->subDays(7));

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $stale = $query->get();

        if ($stale->isEmpty()) {
            $this->info('No pending transactions to sync.');
            return self::SUCCESS;
        }

        $this->info("Found {$stale->count()} pending transactions. Polling Reava Pay...");

        $updated = 0;
        $failed = 0;

        foreach ($stale as $transaction) {
            try {
                $gateway = ReavaPayGateway::forCompany($transaction->company_id);
                $result = $gateway->checkTransactionStatus($transaction->reava_reference);

                if (!$result || !isset($result['data'])) {
                    continue;
                }

                $status = $result['data']['status'] ?? null;

                if ($status === 'completed' && !$transaction->isCompleted()) {
                    $handler->handle([
                        'event' => 'transaction.completed',
                        'data' => $result['data'],
                    ]);
                    $updated++;
                    $this->line("  Updated: {$transaction->gwinto_reference} → completed");
                } elseif ($status === 'failed' && !$transaction->isFailed()) {
                    $handler->handle([
                        'event' => 'transaction.failed',
                        'data' => $result['data'],
                    ]);
                    $updated++;
                    $this->line("  Updated: {$transaction->gwinto_reference} → failed");
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("Reava Pay sync failed for {$transaction->gwinto_reference}: {$e->getMessage()}");
                $this->warn("  Error: {$transaction->gwinto_reference} — {$e->getMessage()}");
            }
        }

        $this->info("Sync complete: {$updated} updated, {$failed} errors.");
        return self::SUCCESS;
    }
}
