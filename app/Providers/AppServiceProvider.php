<?php

namespace App\Providers;

use App\Services\API\BlockStreamAPIService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
