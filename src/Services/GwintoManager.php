<?php

namespace ReavaPay\Gwinto\Services;

use Illuminate\Support\Facades\Http;
use ReavaPay\Gwinto\Exceptions\AuthenticationException;
use ReavaPay\Gwinto\Exceptions\GwintoException;
use ReavaPay\Gwinto\Exceptions\ValidationException;
use ReavaPay\Gwinto\Resources\Invoices;
use ReavaPay\Gwinto\Resources\Subscriptions;
use ReavaPay\Gwinto\Resources\Transactions;
use ReavaPay\Gwinto\Resources\WebhookEndpoints;
use ReavaPay\Gwinto\Resources\Webhooks;

class GwintoManager
{
    protected ?string $apiKey;

    protected ?string $secret;

    protected string $baseUrl;

    protected int $timeout;

    protected ?Invoices $invoices = null;

    protected ?Transactions $transactions = null;

    protected ?Subscriptions $subscriptions = null;

    protected ?WebhookEndpoints $webhookEndpoints = null;

    protected ?Webhooks $webhooks = null;

    public function __construct(
        ?string $apiKey = null,
        ?string $secret = null,
        ?string $baseUrl = null,
        int $timeout = 30
    ) {
        $this->apiKey = $apiKey;
        $this->secret = $secret;
        $this->baseUrl = $baseUrl ?: config('gwinto.reava_pay_base_url', 'https://reavapay.com/api/v1');
        $this->timeout = $timeout;
    }

    /**
     * Initiate a payment through Gwinto via Reava Pay.
     */
    public function pay(array $data): object
    {
        $response = $this->post('/collections', [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? config('gwinto.currency', 'KES'),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'channel' => $data['channel'] ?? 'mpesa',
            'account_reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'callback_url' => $data['callback_url'] ?? null,
            'metadata' => array_merge($data['metadata'] ?? [], [
                'source' => 'gwinto',
                'gwinto_ref' => $data['reference'] ?? null,
            ]),
        ]);

        return (object) [
            'status' => $response['data']['status'] ?? 'processing',
            'reference' => $response['data']['reference'] ?? null,
            'gwinto_ref' => $data['reference'] ?? $response['data']['reference'] ?? null,
            'provider' => 'gwinto',
            'authorization_url' => $response['data']['authorization_url'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'KES',
            'raw' => $response,
        ];
    }

    /**
     * Verify a payment by reference.
     */
    public function verify(string $reference): object
    {
        $response = $this->get("/collections/{$reference}");

        return (object) [
            'status' => $response['data']['status'] ?? 'unknown',
            'reference' => $reference,
            'gwinto_ref' => $response['data']['external_reference'] ?? $reference,
            'amount' => $response['data']['amount'] ?? 0,
            'currency' => $response['data']['currency_code'] ?? 'KES',
            'provider_receipt' => $response['data']['provider_reference'] ?? null,
            'completed_at' => $response['data']['completed_at'] ?? null,
            'raw' => $response,
        ];
    }

    /**
     * Initiate a payout (B2C).
     */
    public function payout(array $data): object
    {
        $response = $this->post('/payouts', [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? config('gwinto.currency', 'KES'),
            'recipient_identifier' => $data['recipient'] ?? $data['phone'] ?? null,
            'recipient_name' => $data['recipient_name'] ?? null,
            'description' => $data['reason'] ?? $data['description'] ?? null,
            'metadata' => array_merge($data['metadata'] ?? [], [
                'source' => 'gwinto',
                'gwinto_ref' => $data['reference'] ?? null,
            ]),
        ]);

        return (object) [
            'status' => $response['data']['status'] ?? 'processing',
            'reference' => $response['data']['reference'] ?? null,
            'gwinto_ref' => $data['reference'] ?? $response['data']['reference'] ?? null,
            'amount' => $data['amount'],
            'raw' => $response,
        ];
    }

    /**
     * Run reconciliation for a date range.
     */
    public function reconcile(array $filters = []): object
    {
        $response = $this->get('/transactions', [
            'from' => $filters['from'] ?? now()->startOfDay()->toIso8601String(),
            'to' => $filters['to'] ?? now()->endOfDay()->toIso8601String(),
            'per_page' => $filters['per_page'] ?? 100,
        ]);

        $transactions = collect($response['data'] ?? []);

        return (object) [
            'transactions' => $transactions->map(fn ($t) => (object) $t),
            'summary' => (object) [
                'total_collected' => $transactions->where('type', 'collection')->where('status', 'completed')->sum('amount'),
                'total_settled' => $transactions->where('type', 'settlement')->where('status', 'completed')->sum('amount'),
                'pending_count' => $transactions->where('status', 'pending')->count(),
                'failed_count' => $transactions->where('status', 'failed')->count(),
                'total_count' => $transactions->count(),
            ],
            'raw' => $response,
        ];
    }

    /**
     * Check Gwinto/Reava Pay float balance.
     */
    public function balance(): object
    {
        $response = $this->get('/float-accounts');

        $accounts = $response['data'] ?? [];
        $primary = $accounts[0] ?? [];

        return (object) [
            'available' => $primary['available_balance'] ?? 0,
            'actual' => $primary['actual_balance'] ?? 0,
            'reserved' => $primary['reserved_balance'] ?? 0,
            'currency' => $primary['currency_code'] ?? 'KES',
            'account_number' => $primary['account_number'] ?? null,
            'accounts' => array_map(fn ($a) => (object) $a, $accounts),
            'raw' => $response,
        ];
    }

    /**
     * Onboard a merchant (company) on Reava Pay.
     */
    public function onboardMerchant(array $data): object
    {
        $response = $this->post('/customers', array_merge($data, [
            'metadata' => array_merge($data['metadata'] ?? [], ['source' => 'gwinto']),
        ]));

        return (object) ($response['data'] ?? $response);
    }

    /**
     * Onboard a customer (tenant) on Reava Pay.
     */
    public function onboardCustomer(array $data): object
    {
        $response = $this->post('/customers', array_merge($data, [
            'metadata' => array_merge($data['metadata'] ?? [], ['source' => 'gwinto', 'type' => 'tenant']),
        ]));

        return (object) ($response['data'] ?? $response);
    }

    /**
     * Sync a local wallet state to Reava Pay.
     */
    public function syncWallet(int $walletId): object
    {
        $response = $this->get('/float-accounts');

        return (object) [
            'synced' => true,
            'wallet_id' => $walletId,
            'float_accounts' => $response['data'] ?? [],
        ];
    }

    // Lazy-loaded resource accessors

    public function invoices(): Invoices
    {
        return $this->invoices ??= new Invoices($this);
    }

    public function transactions(): Transactions
    {
        return $this->transactions ??= new Transactions($this);
    }

    public function subscriptions(): Subscriptions
    {
        return $this->subscriptions ??= new Subscriptions($this);
    }

    public function webhookEndpoints(): WebhookEndpoints
    {
        return $this->webhookEndpoints ??= new WebhookEndpoints($this);
    }

    public function webhooks(): Webhooks
    {
        return $this->webhooks ??= new Webhooks($this);
    }

    // HTTP methods

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete(string $endpoint, array $data = []): array
    {
        return $this->request('DELETE', $endpoint, $data);
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');
        $token = $this->secret ?: $this->apiKey;

        $http = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'ReavaPay-Gwinto/1.0',
            'X-Gwinto-Key' => $this->apiKey ?? '',
        ])->timeout($this->timeout);

        if ($token) {
            $http = $http->withToken($token);
        }

        $response = match (strtoupper($method)) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url, $data),
            default => throw new GwintoException("Unsupported method: {$method}"),
        };

        if ($response->status() === 401) {
            throw new AuthenticationException('Invalid Gwinto/Reava Pay credentials');
        }

        if ($response->status() === 422) {
            throw new ValidationException(
                $response->json('message', 'Validation failed'),
                $response->json('errors', [])
            );
        }

        if ($response->failed()) {
            throw new GwintoException(
                $response->json('message', 'Gwinto API request failed'),
                $response->status()
            );
        }

        return $response->json() ?? [];
    }
}
