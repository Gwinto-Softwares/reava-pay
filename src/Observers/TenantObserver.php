<?php

namespace ReavaPay\Gwinto\Observers;

use ReavaPay\Gwinto\Services\MerchantOnboardingService;
use Illuminate\Support\Facades\Log;

class TenantObserver
{
    public function created($tenant): void
    {
        try {
            $service = app(MerchantOnboardingService::class);
            $service->registerCustomer($tenant->toArray());
            Log::info('Tenant auto-registered on Reava Pay as customer via Gwinto SDK', ['tenant_id' => $tenant->id]);
        } catch (\Exception $e) {
            Log::warning('Gwinto tenant auto-registration failed (non-critical)', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
