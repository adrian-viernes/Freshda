<?php

namespace App\Providers;

use App\Services\RoboflowWorkflowClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RoboflowWorkflowClient::class, function () {
            return RoboflowWorkflowClient::fromConfig();
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
