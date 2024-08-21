<?php

namespace App\Auth\Server\ResponseTypes;

use App\Support\Facades\JWT;
use DateTimeImmutable;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as BaseBearerTokenResponse;

class BearerTokenResponse extends BaseBearerTokenResponse
{
    protected function getExtraParams(AccessTokenEntityInterface $accessToken)
    {
        return [
            'id_token' => $this->getIdToken($accessToken),
        ];
    }

    protected function getIdToken(AccessTokenEntityInterface $accessToken): string
    {
        $userWebId = url('/profile/card#me');
        $clientId = $accessToken->getClient()->getIdentifier();

        return JWT::build()
            ->identifiedBy($accessToken->getIdentifier())
            ->issuedBy(route('home'))
            ->permittedFor('solid', $clientId)
            ->relatedTo($userWebId)
            ->withClaim('azp', $clientId)
            ->withClaim('webid', $userWebId)
            ->issuedAt(new DateTimeImmutable)
            ->expiresAt($accessToken->getExpiryDateTime())
            ->getToken(JWT::signer(), JWT::signingKey())
            ->toString();
    }
}
