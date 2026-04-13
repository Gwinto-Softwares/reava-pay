<?php

namespace ReavaPay\Gwinto\Console;

use Illuminate\Console\Command;
use ReavaPay\Gwinto\Facades\Gwinto;

class StatusCommand extends Command
{
    protected $signature = 'gwinto:status';
    protected $description = 'Check Gwinto/Reava Pay connection status and API key validity';

    public function handle(): int
    {
        $this->info('Checking Gwinto connection...');

        try {
            $balance = Gwinto::balance();

            $this->newLine();
            $this->info('Connection successful!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Status', 'Connected'],
                    ['Environment', config('gwinto.environment', 'live')],
                    ['Currency', $balance->currency],
                    ['Available Balance', number_format($balance->available, 2)],
                    ['Account Number', $balance->account_number ?? 'N/A'],
                    ['Float Accounts', count($balance->accounts)],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Connection failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
