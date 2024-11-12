<?php

namespace App\Providers;

use App\Services\API\BlockStreamAPIService;
use App\Services\BTCService;
use App\Services\ExchangerSettingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BlockStreamAPIService::class, function () {
            return new BlockStreamAPIService();
        });

        $this->app->singleton(BTCService::class, function () {
            return new BTCService($this->app->make(BlockStreamAPIService::class));
        });

        $this->app->singleton(ExchangerSettingService::class, function () {
            return new ExchangerSettingService($this->app->make(BlockStreamAPIService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
