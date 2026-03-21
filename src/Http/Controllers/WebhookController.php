<?php

namespace Gwinto\ReavaPay\Http\Controllers;

use App\Http\Controllers\Controller;
use Gwinto\ReavaPay\Models\ReavaPaySetting;
use Gwinto\ReavaPay\Services\ReavaPayWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook from Reava Pay.
     * Verifies signature against platform OR company-specific webhook secret.
     */
    public function handle(Request $request, ReavaPayWebhookHandler $handler)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Reava-Signature');
        $data = json_decode($payload, true);

        if (!$data) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Try to verify signature against all known secrets
        if ($signature) {
            $verified = false;

            // Collect all webhook secrets: platform + all companies
            $secrets = [];

            $platformSettings = ReavaPaySetting::platform();
            if ($platformSettings?->webhook_secret) {
                $secrets[] = $platformSettings->webhook_secret;
            }

            if (config('reava-pay.webhook_secret')) {
                $secrets[] = config('reava-pay.webhook_secret');
            }

            // Get company-specific secrets
            $companySecrets = ReavaPaySetting::where('scope_type', 'company')
                ->whereNotNull('webhook_secret')
                ->where('is_active', true)
                ->pluck('webhook_secret')
                ->toArray();

            $secrets = array_unique(array_merge($secrets, $companySecrets));

            foreach ($secrets as $secret) {
                $computed = hash_hmac('sha256', $payload, $secret);
                if (hash_equals($computed, $signature)) {
                    $verified = true;
                    break;
                }
            }

            if (!$verified) {
                Log::warning('Reava Pay webhook signature mismatch', [
                    'event' => $data['event'] ?? 'unknown',
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        Log::info('Reava Pay Webhook', [
            'event' => $data['event'] ?? 'unknown',
            'reference' => $data['data']['reference'] ?? null,
        ]);

        $result = $handler->handle($data);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
