<?php

namespace App\Auth\Server;

use League\OAuth2\Server\AuthorizationServer as BaseAuthorizationServer;

class AuthorizationServer extends BaseAuthorizationServer
{
    protected function getResponseType()
    {
        $this->responseType = new ResponseTypes\BearerTokenResponse;

        return parent::getResponseType();
    }
}
