<?php

namespace Gwinto\ReavaPay\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Gwinto\ReavaPay\Models\ReavaPaySetting;
use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Gwinto\ReavaPay\Services\ReavaPayGateway;
use Gwinto\ReavaPay\Services\ReavaPayOnboardingService;
use Illuminate\Http\Request;

class ReavaPaySettingsController extends Controller
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
     * Show company Reava Pay settings.
     */
    public function index()
    {
        $company = $this->getCompany();
        $settings = ReavaPaySetting::forCompanyOrCreate($company->id);
        $platformSettings = ReavaPaySetting::platform();
        $platformActive = $platformSettings && $platformSettings->is_active;

        $stats = [
            'total_transactions' => ReavaPayTransaction::forCompany($company->id)->count(),
            'completed_amount' => ReavaPayTransaction::forCompany($company->id)->completed()->sum('amount'),
            'pending_count' => ReavaPayTransaction::forCompany($company->id)->pending()->count(),
            'this_month' => ReavaPayTransaction::forCompany($company->id)
                ->completed()
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount'),
        ];

        $recentTransactions = ReavaPayTransaction::forCompany($company->id)
            ->with('invoice')
            ->latest()
            ->take(10)
            ->get();

        // Get Reava Pay credentials for display
        $credentials = ReavaPayOnboardingService::getCredentialsForDisplay($company);
        $flashedCredentials = session('reava_pay_credentials');

        return view('reava-pay::company.settings', compact(
            'company',
            'settings',
            'platformActive',
            'stats',
            'recentTransactions',
            'credentials',
            'flashedCredentials'
        ));
    }

    /**
     * Update company Reava Pay settings.
     */
    public function update(Request $request)
    {
        $company = $this->getCompany();

        $request->validate([
            'api_key' => 'nullable|string',
            'public_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'mpesa_enabled' => 'boolean',
            'card_enabled' => 'boolean',
            'bank_transfer_enabled' => 'boolean',
            'auto_credit_wallet' => 'boolean',
            'auto_settle' => 'boolean',
            'settlement_schedule' => 'nullable|in:daily,weekly,monthly',
            'min_settlement_amount' => 'nullable|numeric|min:0',
        ]);

        $settings = ReavaPaySetting::forCompanyOrCreate($company->id);

        $data = $request->only([
            'api_key', 'public_key', 'webhook_secret',
            'settlement_schedule', 'min_settlement_amount',
        ]);

        $data['mpesa_enabled'] = $request->boolean('mpesa_enabled');
        $data['card_enabled'] = $request->boolean('card_enabled');
        $data['bank_transfer_enabled'] = $request->boolean('bank_transfer_enabled');
        $data['auto_credit_wallet'] = $request->boolean('auto_credit_wallet');
        $data['auto_settle'] = $request->boolean('auto_settle');

        if ($request->filled('api_secret')) {
            $settings->api_secret = $request->api_secret;
        }

        $settings->fill($data);
        $settings->save();

        // Update company flags
        $company->update([
            'reava_pay_configured' => $settings->hasValidCredentials() || $this->hasPlatformCredentials(),
        ]);

        return back()->with('success', 'Reava Pay settings updated successfully.');
    }

    /**
     * Toggle Reava Pay for this company.
     */
    public function toggle(Request $request)
    {
        $company = $this->getCompany();
        $settings = ReavaPaySetting::forCompany($company->id);

        $hasCredentials = ($settings && $settings->hasValidCredentials()) || $this->hasPlatformCredentials();

        if (!$hasCredentials) {
            return back()->with('error', 'Configure your Reava Pay credentials or contact admin to enable platform credentials.');
        }

        $newState = !$company->reava_pay_enabled;
        $company->update(['reava_pay_enabled' => $newState]);

        if ($settings) {
            $settings->update(['is_active' => $newState]);
        }

        $status = $newState ? 'enabled' : 'disabled';
        return back()->with('success', "Reava Pay has been {$status} for your company.");
    }

    /**
     * Test API connection with company credentials.
     */
    public function testConnection()
    {
        $company = $this->getCompany();

        try {
            $gateway = ReavaPayGateway::forCompany($company->id);
            $result = $gateway->validateCredentials();

            if ($result['valid']) {
                $settings = ReavaPaySetting::forCompany($company->id);
                if ($settings) {
                    $settings->update([
                        'is_verified' => true,
                        'verified_at' => now(),
                        'last_synced_at' => now(),
                    ]);
                }
                return back()->with('success', 'Connection successful! Your Reava Pay credentials are valid.');
            }

            return back()->with('error', 'Connection failed: ' . $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', 'Connection error: ' . $e->getMessage());
        }
    }

    /**
     * View company transaction history.
     */
    public function transactions(Request $request)
    {
        $company = $this->getCompany();

        $query = ReavaPayTransaction::forCompany($company->id)->with('invoice');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gwinto_reference', 'like', "%{$search}%")
                  ->orWhere('reava_reference', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()->paginate(20);

        $stats = [
            'total_volume' => ReavaPayTransaction::forCompany($company->id)->completed()->sum('amount'),
            'this_month' => ReavaPayTransaction::forCompany($company->id)->completed()
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'total_count' => ReavaPayTransaction::forCompany($company->id)->count(),
            'success_rate' => $this->calculateSuccessRate($company->id),
        ];

        return view('reava-pay::company.transactions', compact('company', 'transactions', 'stats'));
    }

    protected function hasPlatformCredentials(): bool
    {
        $platform = ReavaPaySetting::platform();
        return $platform && $platform->is_active && $platform->hasValidCredentials();
    }

    protected function calculateSuccessRate(int $companyId): float
    {
        $total = ReavaPayTransaction::forCompany($companyId)
            ->whereIn('status', ['completed', 'failed'])
            ->count();

        if ($total === 0) return 0;

        $completed = ReavaPayTransaction::forCompany($companyId)->completed()->count();
        return round(($completed / $total) * 100, 1);
    }
}
