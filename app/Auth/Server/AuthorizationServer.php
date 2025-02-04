<?php

namespace App\Auth\Server;

use App\Auth\Server\ResponseTypes\BearerTokenResponse;
use League\OAuth2\Server\AuthorizationServer as BaseAuthorizationServer;

class AuthorizationServer extends BaseAuthorizationServer
{
    protected function getResponseType()
    {
        $this->responseType = new BearerTokenResponse;

        return parent::getResponseType();
    }
}
