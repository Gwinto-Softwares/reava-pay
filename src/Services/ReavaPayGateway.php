<?php

namespace Gwinto\ReavaPay\Services;

use Gwinto\ReavaPay\Models\ReavaPaySetting;
use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ReavaPayGateway
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null, int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?: config('reava-pay.base_url', 'https://reavapay.com/api/v1');
        $this->timeout = $timeout;
    }

    /**
     * Create a gateway instance for a specific company.
     */
    public static function forCompany(int $companyId): self
    {
        $settings = ReavaPaySetting::effectiveForCompany($companyId);

        if (!$settings || !$settings->hasValidCredentials()) {
            throw new Exception('Reava Pay is not configured for this company.');
        }

        return new self(
            $settings->api_secret,
            $settings->base_url,
            config('reava-pay.timeout', 30)
        );
    }

    /**
     * Create a gateway instance using platform credentials.
     */
    public static function platform(): self
    {
        $settings = ReavaPaySetting::platform();

        if ($settings && $settings->hasValidCredentials()) {
            return new self($settings->api_secret, $settings->base_url);
        }

        // Fallback to config
        return new self(
            config('reava-pay.key'),
            config('reava-pay.base_url'),
            config('reava-pay.timeout', 30)
        );
    }

    /**
     * Initiate a collection (C2B) payment.
     */
    public function initiateCollection(array $data): array
    {
        $response = $this->post('/collections', [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'KES',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'channel' => $data['channel'] ?? 'mpesa',
            'account_reference' => $data['account_reference'] ?? null,
            'description' => $data['description'] ?? null,
            'callback_url' => $data['callback_url'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        return $response;
    }

    /**
     * Initiate a wallet top-up via Reava Pay.
     */
    public function initiateWalletTopUp(array $data): array
    {
        return $this->initiateCollection(array_merge($data, [
            'account_reference' => $data['account_reference'] ?? 'WALLET-TOPUP',
            'description' => $data['description'] ?? 'Gwinto Wallet Top-Up',
        ]));
    }

    /**
     * Initiate an invoice payment via Reava Pay.
     */
    public function initiateInvoicePayment(array $data): array
    {
        return $this->initiateCollection(array_merge($data, [
            'account_reference' => $data['invoice_number'] ?? $data['account_reference'] ?? null,
            'description' => $data['description'] ?? 'Invoice Payment via Gwinto',
        ]));
    }

    /**
     * Check transaction status on Reava Pay.
     */
    public function checkTransactionStatus(string $reference): array
    {
        return $this->get("/collections/{$reference}");
    }

    /**
     * Initiate a payout (B2C).
     */
    public function initiatePayout(array $data): array
    {
        return $this->post('/payouts', [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'KES',
            'recipient_identifier' => $data['recipient_identifier'],
            'recipient_name' => $data['recipient_name'] ?? null,
            'description' => $data['description'] ?? null,
            'callback_url' => $data['callback_url'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Get list of transactions from Reava Pay.
     */
    public function getTransactions(array $filters = []): array
    {
        return $this->get('/transactions', $filters);
    }

    /**
     * Get a specific transaction.
     */
    public function getTransaction(string $id): array
    {
        return $this->get("/transactions/{$id}");
    }

    /**
     * Get float account balances.
     */
    public function getFloatAccounts(): array
    {
        return $this->get('/float-accounts');
    }

    /**
     * Validate API credentials by making a test request.
     */
    public function validateCredentials(): array
    {
        try {
            $response = $this->get('/float-accounts');
            return [
                'valid' => true,
                'message' => 'Credentials are valid',
                'data' => $response,
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhook(string $payload, string $signature, ?string $secret = null): bool
    {
        $webhookSecret = $secret ?: config('reava-pay.webhook_secret');

        if (empty($webhookSecret)) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Make a GET request.
     */
    protected function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Make a POST request.
     */
    protected function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Make an HTTP request to Reava Pay API.
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $http = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout);

            if ($this->apiKey) {
                $http = $http->withToken($this->apiKey);
            }

            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                'PUT' => $http->put($url, $data),
                'PATCH' => $http->patch($url, $data),
                'DELETE' => $http->delete($url, $data),
                default => throw new Exception("Unsupported HTTP method: {$method}"),
            };

            if ($response->failed()) {
                $body = $response->json();
                $message = $body['message'] ?? $body['error'] ?? 'API request failed';

                Log::error('Reava Pay API Error', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $body,
                ]);

                throw new Exception($message, $response->status());
            }

            return $response->json() ?? [];
        } catch (Exception $e) {
            if ($e->getCode() >= 400) {
                throw $e;
            }

            Log::error('Reava Pay Connection Error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Unable to connect to Reava Pay: ' . $e->getMessage());
        }
    }
}
