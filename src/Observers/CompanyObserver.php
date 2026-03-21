<?php

namespace Gwinto\ReavaPay\Observers;

use App\Models\Company;
use Gwinto\ReavaPay\Services\ReavaPayOnboardingService;
use Illuminate\Support\Facades\Log;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     * Auto-register the company as a merchant on Reava Pay.
     */
    public function created(Company $company): void
    {
        try {
            $service = new ReavaPayOnboardingService();
            $result = $service->registerCompany($company);

            if ($result['success']) {
                Log::info('Company auto-registered on Reava Pay', [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                ]);
            } else {
                Log::warning('Company auto-registration on Reava Pay deferred', [
                    'company_id' => $company->id,
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            // Non-critical - company registration should not fail
            Log::warning('Reava Pay company auto-registration failed (non-critical)', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
