<?php

namespace ReavaPay\Gwinto\Observers;

use ReavaPay\Gwinto\Services\MerchantOnboardingService;
use Illuminate\Support\Facades\Log;

class CompanyObserver
{
    public function created($company): void
    {
        try {
            $service = app(MerchantOnboardingService::class);
            $service->registerMerchant($company->toArray());
            Log::info('Company auto-registered on Reava Pay via Gwinto SDK', ['company_id' => $company->id]);
        } catch (\Exception $e) {
            Log::warning('Gwinto company auto-registration failed (non-critical)', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
