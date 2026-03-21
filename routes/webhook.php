<?php

use Gwinto\ReavaPay\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/reava-pay', [WebhookController::class, 'handle'])
    ->name('reava-pay.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
