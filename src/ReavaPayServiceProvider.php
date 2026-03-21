<?php

namespace Gwinto\ReavaPay;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\WalletTransaction;
use Gwinto\ReavaPay\Observers\CompanyObserver;
use Gwinto\ReavaPay\Observers\TenantObserver;
use Gwinto\ReavaPay\Observers\WalletTransactionObserver;
use Gwinto\ReavaPay\Services\ReavaPayGateway;
use Gwinto\ReavaPay\Services\WalletSyncService;
use Illuminate\Support\ServiceProvider;

class ReavaPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/reava-pay.php', 'reava-pay');

        $this->app->singleton(ReavaPayGateway::class, function ($app) {
            return new ReavaPayGateway(
                config('reava-pay.key'),
                config('reava-pay.base_url'),
                config('reava-pay.timeout')
            );
        });

        $this->app->alias(ReavaPayGateway::class, 'reava-pay');

        $this->app->singleton(WalletSyncService::class, function ($app) {
            return new WalletSyncService($app->make(\App\Services\WalletService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/reava-pay.php' => config_path('reava-pay.php'),
        ], 'reava-pay-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'reava-pay-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/webhook.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/company.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/tenant.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'reava-pay');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/reava-pay'),
        ], 'reava-pay-views');

        // Register artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Gwinto\ReavaPay\Console\SyncTransactionsCommand::class,
            ]);

            // Schedule sync every 5 minutes
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                $schedule->command('reava-pay:sync')
                    ->everyFiveMinutes()
                    ->withoutOverlapping()
                    ->runInBackground();
            });
        }

        // Register observers for bi-directional sync and auto-registration
        if (class_exists(WalletTransaction::class)) {
            WalletTransaction::observe(WalletTransactionObserver::class);
        }

        // Auto-register companies as merchants on Reava Pay
        if (class_exists(Company::class)) {
            Company::observe(CompanyObserver::class);
        }

        // Auto-register tenants as customers on Reava Pay
        if (class_exists(Tenant::class)) {
            Tenant::observe(TenantObserver::class);
        }
    }
}
