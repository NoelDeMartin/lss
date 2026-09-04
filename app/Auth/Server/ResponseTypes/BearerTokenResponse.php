<?php

namespace App\Auth\Server\ResponseTypes;

use App\Models\User;
use App\Support\Facades\JWT;
use DateTimeImmutable;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as BaseBearerTokenResponse;
use RuntimeException;

class BearerTokenResponse extends BaseBearerTokenResponse
{
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        return [
            'id_token' => $this->getIdToken($accessToken),
        ];
    }

    protected function getIdToken(AccessTokenEntityInterface $accessToken): string
    {
        /** @var User|null $user */
        $user = User::find($accessToken->getUserIdentifier());

        if (is_null($user)) {
            throw new RuntimeException('User not found for token identifier.');
        }

        /** @var non-empty-string $webId */
        $webId = $user->url('/profile/card#me');
        $clientId = $accessToken->getClient()->getIdentifier();
        /** @var non-empty-string $issuer */
        $issuer = route('home');

        return JWT::build()
            ->identifiedBy($accessToken->getIdentifier())
            ->issuedBy($issuer)
            ->permittedFor('solid', $clientId)
            ->relatedTo($webId)
            ->withClaim('azp', $clientId)
            ->withClaim('webid', $webId)
            ->issuedAt(new DateTimeImmutable)
            ->expiresAt($accessToken->getExpiryDateTime())
            ->getToken(JWT::signer(), JWT::signingKey())
            ->toString();
    }
}
