<?php

namespace ReavaPay\Gwinto\Console;

use Illuminate\Console\Command;
use ReavaPay\Gwinto\Facades\Gwinto;

class ReconcileCommand extends Command
{
    protected $signature = 'gwinto:reconcile {--from= : Start date (Y-m-d)} {--to= : End date (Y-m-d)}';
    protected $description = 'Run payment reconciliation between Gwinto and Reava Pay';

    public function handle(): int
    {
        $from = $this->option('from') ?: now()->startOfDay()->format('Y-m-d');
        $to = $this->option('to') ?: now()->endOfDay()->format('Y-m-d');

        $this->info("Reconciling transactions from {$from} to {$to}...");

        try {
            $report = Gwinto::reconcile(['from' => $from, 'to' => $to]);

            $this->newLine();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Transactions', $report->summary->total_count],
                    ['Total Collected', 'KES ' . number_format($report->summary->total_collected, 2)],
                    ['Total Settled', 'KES ' . number_format($report->summary->total_settled, 2)],
                    ['Pending', $report->summary->pending_count],
                    ['Failed', $report->summary->failed_count],
                ]
            );

            $this->info('Reconciliation complete.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Reconciliation failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
