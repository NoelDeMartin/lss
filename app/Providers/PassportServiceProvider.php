<?php

namespace App\Providers;

use App\Auth\Guards\TokenGuard;
use App\Auth\Server\AuthorizationServer;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Bridge\AccessTokenRepository as AccessTokenRepositoryBridge;
use Laravel\Passport\Bridge\ClientRepository as ClientRepositoryBridge;
use Laravel\Passport\Bridge\ScopeRepository as ScopeRepositoryBridge;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider as BasePassportServiceProvider;
use Laravel\Passport\PassportUserProvider;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use RuntimeException;

class PassportServiceProvider extends BasePassportServiceProvider
{
    public function makeAuthorizationServer(?ResponseTypeInterface $responseType = null): \League\OAuth2\Server\AuthorizationServer
    {
        return new AuthorizationServer(
            $this->app->make(ClientRepositoryBridge::class),
            $this->app->make(AccessTokenRepositoryBridge::class),
            $this->app->make(ScopeRepositoryBridge::class),
            $this->makeCryptKey('private'),
            app('encrypter')->getKey(),
            $responseType ?? Passport::$authorizationServerResponseType
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function makeGuard(array $config): \Laravel\Passport\Guards\TokenGuard
    {
        /** @var string $providerName */
        $providerName = $config['provider'];
        $userProvider = Auth::createUserProvider($providerName);

        if (is_null($userProvider)) {
            throw new RuntimeException("Unable to create user provider [{$providerName}].");
        }

        return new TokenGuard(
            $this->app->make(ResourceServer::class),
            new PassportUserProvider($userProvider, $providerName),
            $this->app->make(ClientRepository::class),
            $this->app->make('encrypter'),
            $this->app->make('request')
        );
    }
}
