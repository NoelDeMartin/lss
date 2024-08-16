<?php

namespace App\Providers;

use App\Services\PodStorageService;
use App\Services\SparqlService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('pod-storage', PodStorageService::class);
        $this->app->singleton('sparql', SparqlService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
