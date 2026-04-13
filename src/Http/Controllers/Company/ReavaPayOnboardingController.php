<?php

namespace Gwinto\ReavaPay\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Gwinto\ReavaPay\Services\ReavaPayOnboardingService;
use Illuminate\Http\Request;

class ReavaPayOnboardingController extends Controller
{
    /**
     * Get the currently authenticated company.
     */
    protected function getCompany(): Company
    {
        if (auth('company')->check()) {
            return auth('company')->user();
        }
        return auth('company_user')->user()->company;
    }

    /**
     * Show the connect page.
     */
    public function showConnect()
    {
        $company = $this->getCompany();

        $existingSettings = \Gwinto\ReavaPay\Models\ReavaPaySetting::forCompany($company->id);
        if ($existingSettings && $existingSettings->is_active) {
            return redirect()->route('company.reava-pay.settings')
                ->with('info', 'Your company is already connected to Reava Pay.');
        }

        $credentials = ReavaPayOnboardingService::getCredentialsForDisplay($company);

        return view('reava-pay::company.connect', compact('company', 'credentials'));
    }

    /**
     * Connect the company to Reava Pay.
     */
    public function connect(Request $request)
    {
        $company = $this->getCompany();
        $service = new ReavaPayOnboardingService();
        $force = $request->boolean('reconnect', false);

        $result = $service->registerCompany($company, $force);

        if ($result['success']) {
            // Flash credentials for one-time display
            if (isset($result['credentials'])) {
                session()->flash('reava_pay_credentials', $result['credentials']);
            }

            return redirect()->route('company.reava-pay.settings')
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Disconnect from Reava Pay.
     */
    public function disconnect(Request $request)
    {
        $company = $this->getCompany();
        $service = new ReavaPayOnboardingService();

        $result = $service->disconnectCompany($company);

        // Redirect to connect page since settings are deleted
        return redirect()->route('company.reava-pay.connect')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
