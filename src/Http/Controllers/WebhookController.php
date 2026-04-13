<?php

namespace ReavaPay\Gwinto\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use ReavaPay\Gwinto\Events\GwintoWebhookReceived;
use ReavaPay\Gwinto\Services\WalletSyncService;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Reava-Signature') ?? $request->header('X-Gwinto-Signature');
        $secret = config('gwinto.webhook_secret');

        if ($secret && $signature) {
            $computed = hash_hmac('sha256', $payload, $secret);
            if (! hash_equals($computed, $signature)) {
                Log::warning('Gwinto webhook signature mismatch', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $data = json_decode($payload, true);

        if (! $data) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $event = $data['event'] ?? 'unknown';

        Log::info('Gwinto webhook received', ['event' => $event]);

        $syncService = app(WalletSyncService::class);

        match (true) {
            // Payment / transaction completion → sync to local invoice
            in_array($event, [
                'transaction.completed',
                'payment.completed',
                'invoice.paid',
                'invoice.payment.received',
            ]) => $syncService->syncFromReavaPay($data),

            default => null,
        };

        // Dispatch event so application code can hook in via listeners
        event(new GwintoWebhookReceived($data, $event));

        return response()->json(['received' => true]);
    }
}
