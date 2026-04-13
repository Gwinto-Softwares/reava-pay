<?php

use Illuminate\Support\Facades\Route;
use ReavaPay\Gwinto\Http\Controllers\ReavaPaySettingsController;
use ReavaPay\Gwinto\Http\Controllers\ReavaPayTransactionsController;

Route::middleware(['web', 'auth'])->prefix('company/reava-pay')->name('gwinto.reava-pay.')->group(function () {
    // Settings
    Route::get('/settings', [ReavaPaySettingsController::class, 'show'])->name('settings');
    Route::post('/settings', [ReavaPaySettingsController::class, 'saveSettings'])->name('settings.save');
    Route::post('/enable', [ReavaPaySettingsController::class, 'enable'])->name('enable');
    Route::post('/disconnect', [ReavaPaySettingsController::class, 'disconnect'])->name('disconnect');
    Route::post('/test-connection', [ReavaPaySettingsController::class, 'testConnection'])->name('test-connection');

    // Transactions
    Route::get('/transactions', [ReavaPayTransactionsController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{id}', [ReavaPayTransactionsController::class, 'show'])->name('transactions.show');
});
