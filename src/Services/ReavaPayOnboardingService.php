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
    /**
     * Register a company on Reava Pay as a new merchant.
     * Calls the public /merchants/register endpoint — no token needed.
     * Returns the merchant's own API credentials and float account.
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

        // Preserve existing metadata so reconnect doesn't lose values on API failure
        $existingSettings = ReavaPaySetting::forCompany($company->id);
        $existingMeta = $existingSettings?->metadata ?? [];

        $merchantId = $existingMeta['reava_merchant_id'] ?? null;
        $merchantName = $existingMeta['reava_merchant_name'] ?? null;
        $floatAccountNumber = $existingMeta['reava_float_account'] ?? null;
        $apiKeyId = $existingMeta['reava_key_id'] ?? null;
        $apiSecret = null;
        $loginPassword = null;
        $webhookSecretFromApi = null;
        $apiRegistered = (bool) ($existingMeta['api_registered'] ?? false);

        // Register as a merchant on Reava Pay (public endpoint, no token needed)
        try {
            $baseUrl = $platformSettings->base_url;

            $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->post(rtrim($baseUrl, '/') . '/merchants/register', [
                    'business_name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone ?? null,
                    'source' => 'gwinto',
                    'metadata' => [
                        'gwinto_company_id' => $company->id,
                    ],
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $data = $result['data'] ?? [];

                $merchantId = $data['merchant_id'] ?? null;
                $merchantName = $data['business_name'] ?? $company->name;
                $floatAccountNumber = $data['float_account']['account_number'] ?? null;
                $apiKeyId = $data['credentials']['key_id'] ?? null;
                $apiSecret = $data['credentials']['secret_key'] ?? null;
                $loginPassword = $data['login_password'] ?? null;
                $webhookSecretFromApi = $data['webhook']['secret'] ?? null;

                $apiRegistered = true;
            } else {
                $body = $response->json();
                Log::error('Reava Pay merchant registration failed', [
                    'company_id' => $company->id,
                    'status' => $response->status(),
                    'response' => $body,
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Reava Pay API connection failed (continuing with local setup)', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }

        // If registered but no password returned, call dedicated reset endpoint
        if ($apiRegistered && !$loginPassword) {
            try {
                $baseUrl = $platformSettings->base_url;
                $resetResponse = Http::withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                    ->timeout(15)
                    ->post(rtrim($baseUrl, '/') . '/merchants/reset-password', [
                        'email' => $company->email,
                        'source' => 'gwinto',
                    ]);

                if ($resetResponse->successful()) {
                    $loginPassword = $resetResponse->json('data.login_password');
                }
            } catch (Exception $e) {
                Log::warning('Reava Pay password reset fallback failed', ['error' => $e->getMessage()]);
            }
        }

        // Use webhook secret from Reava Pay API, or generate local fallback
        $webhookSecret = $webhookSecretFromApi ?? ($existingSettings?->webhook_secret ?? 'whsec_gwinto_' . Str::random(24));

        // Save settings
        $settings = ReavaPaySetting::updateOrCreate(
            ['scope_type' => 'company', 'scope_id' => $company->id],
            [
                'api_key' => $apiKeyId ?? ('pk_' . ($platformSettings->environment === 'production' ? 'live' : 'test') . '_gwinto_' . Str::random(24)),
                'public_key' => $apiKeyId ?? $company->email,
                'webhook_secret' => $webhookSecret,
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
                    'reava_merchant_name' => $merchantName,
                    'reava_float_account' => $floatAccountNumber,
                    'reava_key_id' => $apiKeyId,
                    'reava_login_email' => $company->email,
                    'reava_login_password' => $loginPassword ?? ($existingMeta['reava_login_password'] ?? null),
                    'onboarded_at' => now()->toIso8601String(),
                    'onboarded_via' => 'gwinto_plugin',
                    'api_registered' => $apiRegistered,
                ],
            ]
        );

        // Store the real API secret from Reava Pay (Passport token)
        if ($apiSecret) {
            $settings->api_secret = $apiSecret;
            $settings->save();
        }

        // Update company flags if columns exist
        try {
            $company->update([
                'reava_pay_enabled' => true,
                'reava_pay_configured' => true,
            ]);
        } catch (\Exception $e) {
            Log::debug('Could not update company reava_pay flags', ['error' => $e->getMessage()]);
        }

        Log::info('Company connected to Reava Pay', [
            'company_id' => $company->id,
            'merchant_id' => $merchantId,
            'api_registered' => $apiRegistered,
        ]);

        return [
            'success' => true,
            'message' => $apiRegistered
                ? 'Successfully connected to Reava Pay!'
                : 'Connected to Reava Pay (using platform credentials).',
            'api_registered' => $apiRegistered,
            'settings' => $settings,
            'credentials' => [
                'api_key' => $settings->api_key,
                'api_secret' => $settings->api_secret,
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
            $settings->delete();
        }

        try {
            $company->update([
                'reava_pay_enabled' => false,
                'reava_pay_configured' => false,
            ]);
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
     * Uses the company's own API credentials (not platform).
     */
    public function registerTenant(\App\Models\Tenant $tenant): array
    {
        // Use the company's credentials to register tenant as their customer
        $companyId = $tenant->company_id ?? null;
        $settings = $companyId ? ReavaPaySetting::effectiveForCompany($companyId) : ReavaPaySetting::platform();

        if (!$settings || !$settings->is_active || !$settings->hasValidCredentials()) {
            return ['success' => false, 'message' => 'Reava Pay not configured'];
        }

        try {
            $baseUrl = $settings->base_url;
            $apiKey = $settings->api_secret;

            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post(rtrim($baseUrl, '/') . '/customers', [
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'phone' => $tenant->phone,
                    'metadata' => [
                        'gwinto_tenant_id' => $tenant->id,
                        'gwinto_company_id' => $companyId,
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
            'merchant_name' => $metadata['reava_merchant_name'] ?? null,
            'float_account' => $metadata['reava_float_account'] ?? null,
            'login_email' => $metadata['reava_login_email'] ?? $company->email,
            'login_password' => $metadata['reava_login_password'] ?? null,
            'environment' => $settings->environment,
            'is_active' => $settings->is_active,
            'is_verified' => $settings->is_verified,
            'connected_at' => $metadata['onboarded_at'] ?? null,
            'webhook_url' => url('webhooks/reava-pay'),
            'api_registered' => $metadata['api_registered'] ?? false,
        ];
    }
}
