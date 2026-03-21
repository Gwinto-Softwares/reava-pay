<?php

namespace Gwinto\ReavaPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array initiateCollection(array $data)
 * @method static array initiateWalletTopUp(array $data)
 * @method static array checkTransactionStatus(string $reference)
 * @method static array getTransactions(array $filters = [])
 * @method static array getFloatAccounts()
 * @method static bool verifyWebhook(string $payload, string $signature)
 *
 * @see \Gwinto\ReavaPay\Services\ReavaPayGateway
 */
class ReavaPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'reava-pay';
    }
}
