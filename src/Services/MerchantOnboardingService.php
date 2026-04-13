<?php

namespace ReavaPay\Gwinto\Services;

use ReavaPay\Gwinto\Facades\Gwinto;
use Illuminate\Support\Facades\Log;

class MerchantOnboardingService
{
    /**
     * Register a Gwinto company as a Reava Pay merchant.
     */
    public function registerMerchant(array $companyData): object
    {
        return Gwinto::onboardMerchant([
            'name' => $companyData['name'],
            'email' => $companyData['email'],
            'phone' => $companyData['phone'],
            'business_name' => $companyData['name'],
            'business_type' => $companyData['business_type'] ?? 'company',
            'country' => $companyData['country'] ?? 'Kenya',
            'currency' => config('gwinto.currency', 'KES'),
            'address' => $companyData['address'] ?? null,
            'city' => $companyData['city'] ?? null,
            'registration_number' => $companyData['registration_number'] ?? null,
            'metadata' => [
                'gwinto_company_id' => $companyData['id'] ?? null,
                'source' => 'gwinto_sdk',
                'service_category' => $companyData['service_category'] ?? null,
            ],
        ]);
    }

    /**
     * Register a Gwinto tenant as a Reava Pay customer.
     */
    public function registerCustomer(array $tenantData): object
    {
        return Gwinto::onboardCustomer([
            'name' => $tenantData['name'],
            'email' => $tenantData['email'],
            'phone' => $tenantData['phone'],
            'metadata' => [
                'gwinto_tenant_id' => $tenantData['id'] ?? null,
                'source' => 'gwinto_sdk',
            ],
        ]);
    }
}
