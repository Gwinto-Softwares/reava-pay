<?php

namespace Gwinto\ReavaPay\Observers;

use App\Models\Tenant;
use Gwinto\ReavaPay\Services\ReavaPayOnboardingService;
use Illuminate\Support\Facades\Log;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     * Auto-register the tenant as a customer on Reava Pay.
     */
    public function created(Tenant $tenant): void
    {
        try {
            $service = new ReavaPayOnboardingService();
            $result = $service->registerTenant($tenant);

            if ($result['success']) {
                Log::info('Tenant auto-registered on Reava Pay as customer', [
                    'tenant_id' => $tenant->id,
                    'reava_customer_id' => $result['customer_id'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            // Non-critical - tenant registration should not fail
            Log::warning('Reava Pay tenant auto-registration failed (non-critical)', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
