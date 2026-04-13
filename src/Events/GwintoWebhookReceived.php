<?php

namespace ReavaPay\Gwinto\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GwintoWebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $payload,
        public readonly string $event
    ) {}
}
