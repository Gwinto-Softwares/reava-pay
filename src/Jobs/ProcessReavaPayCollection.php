<?php

namespace Gwinto\ReavaPay\Jobs;

use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Gwinto\ReavaPay\Services\ReavaPayGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessReavaPayCollection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        protected int $transactionId
    ) {
        $this->queue = config('reava-pay.queue', 'default');
    }

    public function handle(): void
    {
        $transaction = ReavaPayTransaction::find($this->transactionId);

        if (!$transaction || !$transaction->isPending()) {
            return;
        }

        try {
            $gateway = $transaction->company_id
                ? ReavaPayGateway::forCompany($transaction->company_id)
                : ReavaPayGateway::platform();

            $response = $gateway->initiateCollection([
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'phone' => $transaction->phone,
                'email' => $transaction->email,
                'channel' => $transaction->channel,
                'account_reference' => $transaction->account_reference,
                'description' => $transaction->description,
                'callback_url' => $transaction->callback_url,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'gwinto_reference' => $transaction->gwinto_reference,
                    'gwinto_type' => $transaction->type,
                ]),
            ]);

            $updateData = [
                'status' => ReavaPayTransaction::STATUS_PROCESSING,
                'reava_reference' => $response['data']['reference'] ?? null,
                'reava_response' => $response,
            ];

            if (!empty($response['data']['authorization_url'])) {
                $updateData['authorization_url'] = $response['data']['authorization_url'];
            }

            $transaction->update($updateData);

            Log::info('Reava Pay collection initiated', [
                'gwinto_ref' => $transaction->gwinto_reference,
                'reava_ref' => $updateData['reava_reference'],
            ]);
        } catch (\Exception $e) {
            $transaction->update([
                'retry_count' => $transaction->retry_count + 1,
            ]);

            Log::error('Reava Pay collection failed', [
                'gwinto_ref' => $transaction->gwinto_reference,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $transaction->markAsFailed('Max retries exceeded: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
