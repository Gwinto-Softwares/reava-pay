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
    public function registerCompany(Company $company): array
    {
        $platformSettings = ReavaPaySetting::platform();

        if (!$platformSettings || !$platformSettings->is_active || !$platformSettings->hasValidCredentials()) {
            return [
                'success' => false,
                'message' => 'Reava Pay platform is not configured. Please contact the administrator.',
            ];
        }

        // Check if already registered
        $existingSettings = ReavaPaySetting::forCompany($company->id);
        if ($existingSettings && $existingSettings->is_verified) {
            return [
                'success' => true,
                'message' => 'Company is already registered on Reava Pay.',
                'settings' => $existingSettings,
            ];
        }

        try {
            $baseUrl = $platformSettings->base_url;
            $apiKey = $platformSettings->api_secret;

            // Step 1: Register as a merchant/sub-merchant on Reava Pay
            $merchantData = [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'business_name' => $company->name,
                'business_type' => $company->business_registration_type ?? 'company',
                'country' => $company->country ?? 'Kenya',
                'currency' => 'KES',
                'address' => $company->address,
                'city' => $company->city,
                'registration_number' => $company->registration_number,
                'kra_pin' => $company->kra_pin ?? null,
                'metadata' => [
                    'gwinto_company_id' => $company->id,
                    'source' => 'gwinto_plugin',
                    'service_category' => $company->serviceCategory?->name ?? null,
                ],
            ];

            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post(rtrim($baseUrl, '/') . '/customers', $merchantData);

            $merchantResult = $response->json();

            if ($response->failed()) {
                throw new Exception($merchantResult['message'] ?? 'Failed to register on Reava Pay');
            }

            $merchantId = $merchantResult['data']['id'] ?? null;
            $merchantRef = $merchantResult['data']['reference'] ?? null;

            // Step 2: Create a float account for the company
            $floatResponse = Http::withToken($apiKey)
                ->timeout(30)
                ->post(rtrim($baseUrl, '/') . '/float-accounts', [
                    'currency_code' => 'KES',
                    'metadata' => [
                        'gwinto_company_id' => $company->id,
                        'merchant_id' => $merchantId,
                        'source' => 'gwinto_plugin',
                    ],
                ]);

            $floatResult = $floatResponse->json();
            $floatAccountNumber = $floatResult['data']['account_number'] ?? null;

            // Step 3: Generate API credentials for the company
            $generatedApiKey = 'pk_' . ($platformSettings->environment === 'production' ? 'live' : 'test') . '_gwinto_' . Str::random(24);
            $generatedSecret = 'sk_' . ($platformSettings->environment === 'production' ? 'live' : 'test') . '_gwinto_' . Str::random(32);
            $generatedWebhookSecret = 'whsec_gwinto_' . Str::random(24);

            // Step 4: Save settings
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
                    'is_verified' => true,
                    'verified_at' => now(),
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
                    ],
                ]
            );

            // Encrypt and store the secret
            $settings->api_secret = $generatedSecret;
            $settings->save();

            // Update company flags
            $company->update([
                'reava_pay_enabled' => true,
                'reava_pay_configured' => true,
            ]);

            Log::info('Company registered on Reava Pay', [
                'company_id' => $company->id,
                'merchant_id' => $merchantId,
                'float_account' => $floatAccountNumber,
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
        } catch (Exception $e) {
            Log::error('Reava Pay company registration failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            // Still create local settings even if API fails (can retry later)
            $settings = ReavaPaySetting::updateOrCreate(
                ['scope_type' => 'company', 'scope_id' => $company->id],
                [
                    'base_url' => $platformSettings->base_url,
                    'environment' => $platformSettings->environment,
                    'default_currency' => 'KES',
                    'is_active' => false,
                    'is_verified' => false,
                    'metadata' => [
                        'registration_error' => $e->getMessage(),
                        'attempted_at' => now()->toIso8601String(),
                    ],
                ]
            );

            return [
                'success' => false,
                'message' => 'Could not connect to Reava Pay: ' . $e->getMessage(),
                'settings' => $settings,
            ];
        }
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

        $company->update([
            'reava_pay_enabled' => false,
        ]);

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
        ];
    }
}
