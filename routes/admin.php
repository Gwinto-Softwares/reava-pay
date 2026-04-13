<?php

use Gwinto\ReavaPay\Http\Controllers\Admin\ReavaPaySettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['web', 'auth:admin'])->group(function () {
    Route::prefix('reava-pay')->name('reava-pay.')->group(function () {
        Route::get('/', [ReavaPaySettingsController::class, 'index'])->name('settings');
        Route::post('/update', [ReavaPaySettingsController::class, 'update'])->name('update');
        Route::post('/toggle-active', [ReavaPaySettingsController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/test-connection', [ReavaPaySettingsController::class, 'testConnection'])->name('test-connection');
        Route::post('/companies/{companyId}/toggle', [ReavaPaySettingsController::class, 'toggleCompany'])->name('company.toggle');
        Route::get('/transactions', [ReavaPaySettingsController::class, 'transactions'])->name('transactions');
    });
});
