<?php

use Gwinto\ReavaPay\Http\Controllers\Company\ReavaPaySettingsController;
use Gwinto\ReavaPay\Http\Controllers\Company\ReavaPayOnboardingController;
use Illuminate\Support\Facades\Route;

Route::prefix('company')->name('company.')->middleware(['web', 'company.auth'])->group(function () {
    Route::prefix('reava-pay')->name('reava-pay.')->group(function () {
        // Onboarding
        Route::get('/connect', [ReavaPayOnboardingController::class, 'showConnect'])->name('connect');
        Route::post('/connect', [ReavaPayOnboardingController::class, 'connect'])->name('connect.process');
        Route::post('/disconnect', [ReavaPayOnboardingController::class, 'disconnect'])->name('disconnect');

        // Settings
        Route::get('/settings', [ReavaPaySettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [ReavaPaySettingsController::class, 'update'])->name('update');
        Route::post('/toggle', [ReavaPaySettingsController::class, 'toggle'])->name('toggle');
        Route::post('/test-connection', [ReavaPaySettingsController::class, 'testConnection'])->name('test-connection');
        Route::get('/transactions', [ReavaPaySettingsController::class, 'transactions'])->name('transactions');
        Route::get('/transactions/{id}', [ReavaPaySettingsController::class, 'transactionDetail'])->name('transactions.detail');
        Route::post('/transactions/{id}/sync', [ReavaPaySettingsController::class, 'syncTransaction'])->name('transactions.sync');
    });
});
