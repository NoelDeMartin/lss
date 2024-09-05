<?php

namespace App\Providers;

use App\Models\Client;
use App\Services\CloudService;
use App\Services\JWTService;
use App\Services\SolidService;
use App\Services\SparqlService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('cloud', CloudService::class);
        $this->app->singleton('jwt', JWTService::class);
        $this->app->singleton('solid', SolidService::class);
        $this->app->singleton('sparql', SparqlService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::useClientModel(Client::class);
        Passport::tokensCan([
            'webid' => 'Log in using WebID',
            'openid' => 'Verify OpenID identity',
            'offline_access' => 'Get refresh tokens',
        ]);
        Request::macro('wantsTurtle', function () {
            $acceptable = $this->getAcceptableContentTypes();

            return isset($acceptable[0]) && str_contains(strtolower($acceptable[0]), 'text/turtle');
        });
        Request::macro('username', function () {
            $parts = parse_url(config('app.url'));

            preg_match('/'.preg_quote($parts['scheme']).'\:\/\/([^.]+)\.'.preg_quote($parts['host']).'/', $this->url(), $matches);

            return $matches[1] ?? null;
        });
    }
}
