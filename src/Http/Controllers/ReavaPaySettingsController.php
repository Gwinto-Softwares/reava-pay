<?php

namespace ReavaPay\Gwinto\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use ReavaPay\Gwinto\Facades\Gwinto;
use ReavaPay\Gwinto\Services\GwintoManager;

class ReavaPaySettingsController extends Controller
{
    public function show(): Response
    {
        $credentials = $this->loadCredentials();
        $stats = $this->loadStats();
        $channels = config('gwinto.channels', []);
        $isConnected = $this->checkConnection();
        $webhookUrl = url(config('gwinto.webhook_path', 'webhooks/gwinto'));

        return Inertia::render('GwintoReavaPay/Settings', [
            'credentials' => $credentials,
            'stats' => $stats,
            'channels' => $channels,
            'isConnected' => $isConnected,
            'webhookUrl' => $webhookUrl,
            'environment' => config('gwinto.environment', 'live'),
            'connectedSince' => config('gwinto.connected_since'),
            'walletSettings' => [
                'auto_credit' => config('gwinto.auto_sync_wallets', true),
                'auto_settle' => config('gwinto.auto_settle', false),
                'settlement_schedule' => config('gwinto.settlement_schedule', 'manual'),
                'min_settlement_amount' => config('gwinto.min_settlement_amount', 1000),
            ],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string|max:255',
            'public_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string|max:1000',
            'webhook_secret' => 'nullable|string|max:1000',
            'channels' => 'nullable|array',
            'channels.mpesa' => 'nullable|boolean',
            'channels.card' => 'nullable|boolean',
            'channels.bank_transfer' => 'nullable|boolean',
            'auto_credit' => 'nullable|boolean',
            'auto_settle' => 'nullable|boolean',
            'settlement_schedule' => 'nullable|in:manual,daily,weekly,monthly',
            'min_settlement_amount' => 'nullable|numeric|min:0',
        ]);

        $envLines = [];

        if (! empty($validated['api_key'])) {
            $envLines['GWINTO_API_KEY'] = $validated['api_key'];
        }
        if (! empty($validated['api_secret'])) {
            $envLines['GWINTO_SECRET'] = $validated['api_secret'];
        }
        if (! empty($validated['webhook_secret'])) {
            $envLines['GWINTO_WEBHOOK_SECRET'] = $validated['webhook_secret'];
        }

        $this->updateEnvFile($envLines);

        return redirect()->back()->with('success', 'Reava Pay settings saved successfully.');
    }

    public function testConnection(): JsonResponse
    {
        try {
            $balance = Gwinto::balance();

            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
                'balance' => $balance->available,
                'currency' => $balance->currency,
                'account_number' => $balance->account_number,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gwinto connection test failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function enable(Request $request): RedirectResponse
    {
        $this->updateEnvFile(['GWINTO_ENABLED' => 'true']);

        return redirect()->back()->with('success', 'Reava Pay integration enabled.');
    }

    public function disconnect(): RedirectResponse
    {
        $this->updateEnvFile(['GWINTO_ENABLED' => 'false']);

        return redirect()->back()->with('success', 'Reava Pay integration disconnected.');
    }

    private function loadCredentials(): array
    {
        return [
            'merchant_id' => config('gwinto.merchant_id'),
            'login_email' => config('gwinto.login_email'),
            'float_account' => config('gwinto.float_account'),
            'api_key' => config('gwinto.api_key'),
            'public_key' => config('gwinto.reava_pay_public_key'),
            'has_own_credentials' => ! empty(config('gwinto.api_key')),
            'has_platform_credentials' => ! empty(env('REAVA_PAY_PLATFORM_KEY')),
            'verified' => (bool) config('gwinto.credentials_verified', false),
            'last_verified' => config('gwinto.last_verified'),
        ];
    }

    private function loadStats(): array
    {
        try {
            $manager = app(GwintoManager::class);
            $result = $manager->reconcile(['from' => now()->startOfMonth()->toIso8601String()]);

            return [
                'transaction_count' => $result->summary->total_count ?? 0,
                'total_collected' => $result->summary->total_collected ?? 0,
                'total_settled' => $result->summary->total_settled ?? 0,
                'pending_count' => $result->summary->pending_count ?? 0,
                'currency' => 'KES',
            ];
        } catch (\Throwable) {
            return [
                'transaction_count' => 0,
                'total_collected' => 0,
                'total_settled' => 0,
                'pending_count' => 0,
                'currency' => 'KES',
            ];
        }
    }

    private function checkConnection(): bool
    {
        if (! config('gwinto.api_key')) {
            return false;
        }

        try {
            Gwinto::balance();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $contents = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $value = (string) $value;
            $escapedValue = str_contains($value, ' ') ? '"'.$value.'"' : $value;

            if (str_contains($contents, "{$key}=")) {
                $contents = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $contents);
            } else {
                $contents .= "\n{$key}={$escapedValue}";
            }
        }

        file_put_contents($envPath, $contents);
    }
}
