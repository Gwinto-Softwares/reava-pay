<?php

namespace ReavaPay\Gwinto\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static object pay(array $data)
 * @method static object verify(string $reference)
 * @method static object payout(array $data)
 * @method static object reconcile(array $filters = [])
 * @method static \ReavaPay\Gwinto\Resources\Transactions transactions()
 * @method static \ReavaPay\Gwinto\Resources\Subscriptions subscriptions()
 * @method static object balance()
 * @method static object onboardMerchant(array $data)
 * @method static object onboardCustomer(array $data)
 * @method static object syncWallet(int $walletId)
 * @method static \ReavaPay\Gwinto\Resources\WebhookEndpoints webhookEndpoints()
 * @method static \ReavaPay\Gwinto\Resources\Webhooks webhooks()
 * @method static void fake(array $responses = [])
 * @method static \ReavaPay\Gwinto\Testing\GwintoFakeResponse response(array $data = [])
 *
 * @see \ReavaPay\Gwinto\Services\GwintoManager
 */
class Gwinto extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'gwinto';
    }
}
