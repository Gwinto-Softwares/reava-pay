<?php

namespace Gwinto\ReavaPay\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gwinto\ReavaPay\Models\ReavaPaySetting;
use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Gwinto\ReavaPay\Services\ReavaPayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReavaPaySettingsController extends Controller
{
    /**
     * Show the Reava Pay admin settings page.
     */
    public function index()
    {
        $settings = ReavaPaySetting::platformOrCreate();

        $stats = [
            'total_transactions' => ReavaPayTransaction::count(),
            'completed_transactions' => ReavaPayTransaction::completed()->count(),
            'failed_transactions' => ReavaPayTransaction::failed()->count(),
            'total_volume' => ReavaPayTransaction::completed()->sum('amount'),
            'this_month_volume' => ReavaPayTransaction::completed()
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'active_companies' => ReavaPaySetting::where('scope_type', 'company')
                ->where('is_active', true)
                ->count(),
        ];

        $recentTransactions = ReavaPayTransaction::with(['company'])
            ->latest()
            ->take(10)
            ->get();

        $companySettings = ReavaPaySetting::where('scope_type', 'company')
            ->with('company')
            ->latest()
            ->get();

        return view('reava-pay::admin.settings', compact(
            'settings',
            'stats',
            'recentTransactions',
            'companySettings'
        ));
    }

    /**
     * Update platform-level Reava Pay settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'public_key' => 'required|string',
            'api_secret' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'base_url' => 'required|url',
            'environment' => 'required|in:sandbox,production',
            'default_currency' => 'required|string|max:10',
            'mpesa_enabled' => 'boolean',
            'card_enabled' => 'boolean',
            'bank_transfer_enabled' => 'boolean',
            'auto_credit_wallet' => 'boolean',
            'min_transaction_amount' => 'required|numeric|min:0',
            'max_transaction_amount' => 'required|numeric|gt:min_transaction_amount',
        ]);

        $settings = ReavaPaySetting::platformOrCreate();

        $data = $request->only([
            'api_key', 'public_key', 'webhook_secret', 'base_url',
            'environment', 'default_currency',
            'min_transaction_amount', 'max_transaction_amount',
        ]);

        $data['mpesa_enabled'] = $request->boolean('mpesa_enabled');
        $data['card_enabled'] = $request->boolean('card_enabled');
        $data['bank_transfer_enabled'] = $request->boolean('bank_transfer_enabled');
        $data['auto_credit_wallet'] = $request->boolean('auto_credit_wallet');

        // Only update secret if provided
        if ($request->filled('api_secret')) {
            $settings->api_secret = $request->api_secret;
        }

        $settings->fill($data);
        $settings->save();

        return back()->with('success', 'Reava Pay platform settings updated successfully.');
    }

    /**
     * Activate/deactivate platform settings.
     */
    public function toggleActive(Request $request)
    {
        $settings = ReavaPaySetting::platformOrCreate();

        if (!$settings->is_active && !$settings->hasValidCredentials()) {
            return back()->with('error', 'Cannot activate Reava Pay without valid API credentials.');
        }

        $settings->update(['is_active' => !$settings->is_active]);

        $status = $settings->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Reava Pay has been {$status}.");
    }

    /**
     * Test API connection.
     */
    public function testConnection()
    {
        $settings = ReavaPaySetting::platform();

        if (!$settings || !$settings->hasValidCredentials()) {
            return back()->with('error', 'Please configure API credentials first.');
        }

        try {
            $gateway = new ReavaPayGateway(
                $settings->api_secret,
                $settings->base_url
            );

            $result = $gateway->validateCredentials();

            if ($result['valid']) {
                $settings->update([
                    'is_verified' => true,
                    'verified_at' => now(),
                    'last_synced_at' => now(),
                ]);
                return back()->with('success', 'Connection successful! API credentials are valid.');
            }

            return back()->with('error', 'Connection failed: ' . $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', 'Connection error: ' . $e->getMessage());
        }
    }

    /**
     * Toggle a company's Reava Pay access.
     */
    public function toggleCompany(Request $request, int $companyId)
    {
        $companySettings = ReavaPaySetting::forCompany($companyId);

        if (!$companySettings) {
            return back()->with('error', 'Company has not configured Reava Pay yet.');
        }

        $companySettings->update(['is_active' => !$companySettings->is_active]);

        $status = $companySettings->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Company Reava Pay access has been {$status}.");
    }

    /**
     * View transactions with filters.
     */
    public function transactions(Request $request)
    {
        $query = ReavaPayTransaction::with(['company', 'invoice']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gwinto_reference', 'like', "%{$search}%")
                  ->orWhere('reava_reference', 'like', "%{$search}%")
                  ->orWhere('provider_reference', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()->paginate(25);

        $stats = [
            'total' => ReavaPayTransaction::count(),
            'completed' => ReavaPayTransaction::completed()->count(),
            'pending' => ReavaPayTransaction::pending()->count(),
            'failed' => ReavaPayTransaction::failed()->count(),
            'volume' => ReavaPayTransaction::completed()->sum('amount'),
        ];

        return view('reava-pay::admin.transactions', compact('transactions', 'stats'));
    }
}
