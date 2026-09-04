<?php

namespace App\Providers;

use App\Models\Client;
use App\Services\CloudService;
use App\Services\JWTService;
use App\Services\SolidService;
use App\Services\SparqlService;
use App\Support\Testing\Constraints\IsTurtle;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\Assert;
use Illuminate\Testing\TestResponse;
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
        Passport::authorizationView('vendor.passport.authorize');
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
            /** @var string|null $appUrl */
            $appUrl = config('app.url');
            $parts = is_string($appUrl) ? parse_url($appUrl) : false;

            if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
                return null;
            }

            preg_match('/' . preg_quote($parts['scheme'], '/') . '\:\/\/([^.]+)\.' . preg_quote($parts['host'], '/') . '/', $this->url(), $matches);

            return $matches[1] ?? null;
        });

        if ($this->app->runningUnitTests()) {
            TestResponse::macro('assertValidTurtle', function () {
                $uri = ! is_null($this->baseRequest) ? $this->baseRequest->getRequestUri() : null;
                Assert::assertThat($this->content(), new IsTurtle($uri));
            });
        }
    }
}
