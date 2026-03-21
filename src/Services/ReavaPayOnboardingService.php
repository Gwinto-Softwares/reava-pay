<?php

namespace Gwinto\ReavaPay\Services;

use App\Models\Company;
use Gwinto\ReavaPay\Models\ReavaPaySetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ReavaPayOnboardingService
{
    protected ReavaPayGateway $gateway;

    public function __construct()
    {
        $this->gateway = ReavaPayGateway::platform();
    }

    /**
     * Register a company on Reava Pay and create their merchant account.
     * Called during company registration or when clicking "Connect Reava Pay".
     */
    public function registerCompany(Company $company, bool $force = false): array
    {
        $platformSettings = ReavaPaySetting::platform();

        if (!$platformSettings || !$platformSettings->hasValidCredentials()) {
            return [
                'success' => false,
                'message' => 'Reava Pay platform is not configured. Please contact the administrator.',
            ];
        }

        // Check if already registered (skip if forcing reconnect)
        if (!$force) {
            $existingSettings = ReavaPaySetting::forCompany($company->id);
            if ($existingSettings && $existingSettings->is_verified) {
                return [
                    'success' => true,
                    'message' => 'Company is already registered on Reava Pay.',
                    'settings' => $existingSettings,
                ];
            }
        }

        $merchantId = null;
        $merchantRef = null;
        $floatAccountNumber = null;
        $apiRegistered = false;

        // Try to connect to the Reava Pay API (non-blocking if it fails)
        try {
            $baseUrl = $platformSettings->base_url;
            $apiKey = $platformSettings->api_secret;

            // Step 1: Get the authenticated merchant's info from /me
            $meResponse = Http::withToken($apiKey)
                ->timeout(15)
                ->get(rtrim($baseUrl, '/') . '/me');

            if ($meResponse->successful()) {
                $meResult = $meResponse->json();
                $merchantId = $meResult['data']['id'] ?? null;
                $merchantRef = $meResult['data']['business_name'] ?? $meResult['data']['name'] ?? null;

                // Step 2: Get existing float accounts (or create one)
                $floatResponse = Http::withToken($apiKey)
                    ->timeout(15)
                    ->get(rtrim($baseUrl, '/') . '/float-accounts');

                if ($floatResponse->successful()) {
                    $floatResult = $floatResponse->json();
                    $accounts = $floatResult['data'] ?? [];

                    if (!empty($accounts)) {
                        // Use the first active KES float account
                        $kesAccount = collect($accounts)->firstWhere('currency', 'KES') ?? $accounts[0];
                        $floatAccountNumber = $kesAccount['account_number'] ?? null;
                    } else {
                        // No float account exists — create one
                        $createFloatResponse = Http::withToken($apiKey)
                            ->timeout(15)
                            ->post(rtrim($baseUrl, '/') . '/float-accounts', [
                                'name' => $company->name . ' KES Float',
                                'currency_code' => 'KES',
                            ]);

                        if ($createFloatResponse->successful()) {
                            $createFloatResult = $createFloatResponse->json();
                            $floatAccountNumber = $createFloatResult['data']['account_number'] ?? null;
                        }
                    }
                }

                $apiRegistered = true;
            }
        } catch (Exception $e) {
            Log::warning('Reava Pay API connection failed (continuing with local setup)', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Generate local credentials regardless of API status
        $generatedApiKey = 'pk_' . ($platformSettings->environment === 'production' ? 'live' : 'test') . '_gwinto_' . Str::random(24);
        $generatedSecret = 'sk_' . ($platformSettings->environment === 'production' ? 'live' : 'test') . '_gwinto_' . Str::random(32);
        $generatedWebhookSecret = 'whsec_gwinto_' . Str::random(24);

        // Save settings
        $settings = ReavaPaySetting::updateOrCreate(
            ['scope_type' => 'company', 'scope_id' => $company->id],
            [
                'api_key' => $generatedApiKey,
                'public_key' => $generatedApiKey,
                'webhook_secret' => $generatedWebhookSecret,
                'base_url' => $platformSettings->base_url,
                'environment' => $platformSettings->environment,
                'default_currency' => 'KES',
                'mpesa_enabled' => $platformSettings->mpesa_enabled,
                'card_enabled' => $platformSettings->card_enabled,
                'bank_transfer_enabled' => $platformSettings->bank_transfer_enabled,
                'auto_credit_wallet' => true,
                'is_active' => true,
                'is_verified' => $apiRegistered,
                'verified_at' => $apiRegistered ? now() : null,
                'last_synced_at' => now(),
                'metadata' => [
                    'reava_merchant_id' => $merchantId,
                    'reava_merchant_ref' => $merchantRef,
                    'reava_float_account' => $floatAccountNumber,
                    'generated_api_key' => $generatedApiKey,
                    'generated_secret' => $generatedSecret,
                    'reava_login_email' => $company->email,
                    'onboarded_at' => now()->toIso8601String(),
                    'onboarded_via' => 'gwinto_plugin',
                    'api_registered' => $apiRegistered,
                ],
            ]
        );

        // Encrypt and store the secret
        $settings->api_secret = $generatedSecret;
        $settings->save();

        // Update company flags if columns exist
        try {
            $company->update([
                'reava_pay_enabled' => true,
                'reava_pay_configured' => true,
            ]);
        } catch (\Exception $e) {
            // Columns may not exist yet — non-critical
            Log::debug('Could not update company reava_pay flags', ['error' => $e->getMessage()]);
        }

        Log::info('Company connected to Reava Pay', [
            'company_id' => $company->id,
            'merchant_id' => $merchantId,
            'api_registered' => $apiRegistered,
        ]);

        return [
            'success' => true,
            'message' => 'Successfully connected to Reava Pay!',
            'settings' => $settings,
            'credentials' => [
                'api_key' => $generatedApiKey,
                'api_secret' => $generatedSecret,
                'login_email' => $company->email,
                'merchant_id' => $merchantId,
                'float_account' => $floatAccountNumber,
            ],
        ];
    }

    /**
     * Disconnect a company from Reava Pay.
     */
    public function disconnectCompany(Company $company): array
    {
        $settings = ReavaPaySetting::forCompany($company->id);

        if ($settings) {
            $settings->update([
                'is_active' => false,
            ]);
        }

        try {
            $company->update(['reava_pay_enabled' => false]);
        } catch (\Exception $e) {
            // Column may not exist — non-critical
        }

        return [
            'success' => true,
            'message' => 'Disconnected from Reava Pay.',
        ];
    }

    /**
     * Register a tenant on Reava Pay as a customer.
     * Called during tenant registration.
     */
    public function registerTenant(\App\Models\Tenant $tenant): array
    {
        $platformSettings = ReavaPaySetting::platform();

        if (!$platformSettings || !$platformSettings->is_active || !$platformSettings->hasValidCredentials()) {
            return ['success' => false, 'message' => 'Reava Pay platform not configured'];
        }

        try {
            $baseUrl = $platformSettings->base_url;
            $apiKey = $platformSettings->api_secret;

            // Register as a customer on Reava Pay
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post(rtrim($baseUrl, '/') . '/customers', [
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'phone' => $tenant->phone,
                    'metadata' => [
                        'gwinto_tenant_id' => $tenant->id,
                        'source' => 'gwinto_plugin',
                        'type' => 'tenant',
                    ],
                ]);

            $result = $response->json();
            $customerId = $result['data']['id'] ?? null;
            $customerRef = $result['data']['reference'] ?? null;

            Log::info('Tenant registered on Reava Pay', [
                'tenant_id' => $tenant->id,
                'reava_customer_id' => $customerId,
            ]);

            return [
                'success' => true,
                'customer_id' => $customerId,
                'customer_ref' => $customerRef,
            ];
        } catch (Exception $e) {
            Log::warning('Tenant Reava Pay registration failed (non-critical)', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Reava Pay credentials for display.
     */
    public static function getCredentialsForDisplay(Company $company): ?array
    {
        $settings = ReavaPaySetting::forCompany($company->id);

        if (!$settings) {
            return null;
        }

        $metadata = $settings->metadata ?? [];

        return [
            'api_key' => $settings->api_key,
            'api_secret' => $settings->api_secret,
            'public_key' => $settings->public_key,
            'merchant_id' => $metadata['reava_merchant_id'] ?? null,
            'merchant_ref' => $metadata['reava_merchant_ref'] ?? null,
            'float_account' => $metadata['reava_float_account'] ?? null,
            'login_email' => $metadata['reava_login_email'] ?? $company->email,
            'environment' => $settings->environment,
            'is_active' => $settings->is_active,
            'is_verified' => $settings->is_verified,
            'connected_at' => $metadata['onboarded_at'] ?? null,
            'webhook_url' => url('webhooks/reava-pay'),
            'api_registered' => $metadata['api_registered'] ?? false,
        ];
    }
}
