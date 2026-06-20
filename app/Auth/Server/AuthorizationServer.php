<?php

namespace App\Auth\Server;

use App\Auth\Server\ResponseTypes\BearerTokenResponse;
use League\OAuth2\Server\AuthorizationServer as BaseAuthorizationServer;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

class AuthorizationServer extends BaseAuthorizationServer
{
    protected function getResponseType(): ResponseTypeInterface
    {
        $this->responseType = new BearerTokenResponse;

        return parent::getResponseType();
    }
}
