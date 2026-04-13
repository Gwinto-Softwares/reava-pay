<?php

namespace ReavaPay\Gwinto\Resources;

class Webhooks
{
    /**
     * Verify an incoming webhook signature.
     */
    public function verify(string $payload, string $signature, ?string $secret = null): bool
    {
        $secret = $secret ?: config('gwinto.webhook_secret');

        if (empty($secret)) {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $signature);
    }
}
