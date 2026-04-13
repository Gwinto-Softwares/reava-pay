<?php

namespace ReavaPay\Gwinto;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReavaPay\Gwinto\Http\Controllers\WebhookController;
use ReavaPay\Gwinto\Services\GwintoManager;
use ReavaPay\Gwinto\Services\MerchantOnboardingService;
use ReavaPay\Gwinto\Services\WalletSyncService;

class GwintoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/gwinto.php', 'gwinto');

        $this->app->singleton(GwintoManager::class, function ($app) {
            return new GwintoManager(
                config('gwinto.api_key'),
                config('gwinto.secret'),
                config('gwinto.reava_pay_base_url'),
                config('gwinto.timeout')
            );
        });

        $this->app->alias(GwintoManager::class, 'gwinto');

        $this->app->singleton(WalletSyncService::class);
        $this->app->singleton(MerchantOnboardingService::class);
    }

    public function boot(): void
    {
        // Config
        $this->publishes([
            __DIR__.'/../config/gwinto.php' => config_path('gwinto.php'),
        ], 'gwinto-config');

        // Migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'gwinto-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Vue pages (publishable)
        $this->publishes([
            __DIR__.'/../resources/js/Pages/GwintoReavaPay' => resource_path('js/Pages/GwintoReavaPay'),
        ], 'gwinto-views');

        // Register model observers for auto-onboarding
        $this->registerObservers();

        // Webhook route macro
        if (! $this->app->routesAreCached()) {
            Route::macro('gwintoWebhooks', function (string $path = '/webhooks/gwinto') {
                Route::post($path, [
                    WebhookController::class, 'handle',
                ])->name('gwinto.webhook');
            });
        }

        // Console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\StatusCommand::class,
                Console\ReconcileCommand::class,
                Console\TransactionsCommand::class,
            ]);
        }
    }

    protected function registerObservers(): void
    {
        if (! config('gwinto.api_key')) {
            return;
        }

        // Auto-onboard companies as Reava Pay merchants
        if (config('gwinto.auto_onboard_companies') && class_exists(Company::class)) {
            Company::observe(Observers\CompanyObserver::class);
        }

        // Auto-onboard tenants as Reava Pay customers
        if (config('gwinto.auto_onboard_tenants') && class_exists(Tenant::class)) {
            Tenant::observe(Observers\TenantObserver::class);
        }

        // Bi-directional wallet sync
        if (config('gwinto.auto_sync_wallets') && class_exists(WalletTransaction::class)) {
            WalletTransaction::observe(Observers\WalletTransactionObserver::class);
        }
    }
}
