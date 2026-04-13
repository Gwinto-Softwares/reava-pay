<?php

namespace ReavaPay\Gwinto\Console;

use Illuminate\Console\Command;
use ReavaPay\Gwinto\Facades\Gwinto;

class TransactionsCommand extends Command
{
    protected $signature = 'gwinto:transactions {--limit=10 : Number of transactions to show}';
    protected $description = 'List recent Gwinto/Reava Pay transactions';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        try {
            $result = Gwinto::transactions()->list(['per_page' => $limit]);
            $transactions = $result['data'] ?? [];

            if (empty($transactions)) {
                $this->info('No transactions found.');
                return self::SUCCESS;
            }

            $rows = array_map(fn($t) => [
                $t['uuid'] ?? 'N/A',
                $t['type'] ?? 'N/A',
                ($t['currency_code'] ?? 'KES') . ' ' . number_format($t['amount'] ?? 0, 2),
                $t['status'] ?? 'N/A',
                $t['created_at'] ?? 'N/A',
            ], $transactions);

            $this->table(['Reference', 'Type', 'Amount', 'Status', 'Date'], $rows);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
