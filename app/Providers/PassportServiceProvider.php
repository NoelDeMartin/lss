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
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;

class PassportServiceProvider extends BasePassportServiceProvider
{
    public function makeAuthorizationServer()
    {
        return new AuthorizationServer(
            $this->app->make(ClientRepositoryBridge::class),
            $this->app->make(AccessTokenRepositoryBridge::class),
            $this->app->make(ScopeRepositoryBridge::class),
            $this->makeCryptKey('private'),
            app('encrypter')->getKey(),
            Passport::$authorizationServerResponseType
        );
    }

    protected function makeGuard(array $config)
    {
        return new TokenGuard(
            $this->app->make(ResourceServer::class),
            new PassportUserProvider(Auth::createUserProvider($config['provider']), $config['provider']),
            $this->app->make(TokenRepository::class),
            $this->app->make(ClientRepository::class),
            $this->app->make('encrypter'),
            $this->app->make('request')
        );
    }
}
