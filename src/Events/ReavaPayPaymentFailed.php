<?php

namespace Gwinto\ReavaPay\Events;

use Gwinto\ReavaPay\Models\ReavaPayTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReavaPayPaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ReavaPayTransaction $transaction
    ) {}
}
