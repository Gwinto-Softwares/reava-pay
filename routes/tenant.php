<?php

use Gwinto\ReavaPay\Http\Controllers\Tenant\ReavaPayPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('tenant')->name('tenant.')->middleware(['web', 'auth:tenant'])->group(function () {
    Route::prefix('reava-pay')->name('reava-pay.')->group(function () {
        Route::get('/pay-invoice/{invoiceId}', [ReavaPayPaymentController::class, 'payInvoice'])->name('pay-invoice');
        Route::post('/pay-invoice/{invoiceId}', [ReavaPayPaymentController::class, 'processInvoicePayment'])->name('pay-invoice.process');
        Route::get('/wallet-topup', [ReavaPayPaymentController::class, 'walletTopUp'])->name('wallet-topup');
        Route::post('/wallet-topup', [ReavaPayPaymentController::class, 'processWalletTopUp'])->name('wallet-topup.process');
        Route::get('/history', [ReavaPayPaymentController::class, 'history'])->name('history');
    });
});

// Payment status - accessible without strict auth for callback redirects
Route::middleware('web')->group(function () {
    Route::get('/reava-pay/status/{reference}', [ReavaPayPaymentController::class, 'paymentStatus'])
        ->name('reava-pay.payment.status')
        ->middleware('auth:tenant');
    Route::get('/reava-pay/check-status/{reference}', [ReavaPayPaymentController::class, 'checkStatus'])
        ->name('reava-pay.payment.check-status')
        ->middleware('auth:tenant');
});
