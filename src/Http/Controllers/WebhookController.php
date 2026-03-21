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
     */
    public function handle(Request $request, ReavaPayWebhookHandler $handler)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Reava-Signature');

        // Verify webhook signature
        $settings = ReavaPaySetting::platform();
        $webhookSecret = $settings?->webhook_secret ?: config('reava-pay.webhook_secret');

        if ($webhookSecret && $signature) {
            $computedSignature = hash_hmac('sha256', $payload, $webhookSecret);

            if (!hash_equals($computedSignature, $signature)) {
                Log::warning('Reava Pay webhook signature mismatch');
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $data = json_decode($payload, true);

        if (!$data) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Reava Pay Webhook', ['event' => $data['event'] ?? 'unknown']);

        $result = $handler->handle($data);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
