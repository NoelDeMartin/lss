<?php

namespace App\Auth\Guards;

use App\Support\Facades\JWT;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\Guards\TokenGuard as BaseTokenGuard;
use Laravel\Passport\Token;

class TokenGuard extends BaseTokenGuard
{
    public function user(): ?Authenticatable
    {
        if (! is_null($this->user)) {
            /** @var OAuthenticatable|null $user */
            $user = $this->user;

            return $user;
        }

        if ($this->usingDPoP($this->request)) {
            $user = $this->authenticateViaDPoP($this->request);

            /** @var OAuthenticatable|null $user */
            return $this->user = $user;
        }

        /** @var OAuthenticatable|null $user */
        $user = parent::user();

        return $user;
    }

    protected function usingDPoP(Request $request): bool
    {
        $header = $request->header('Authorization', '');

        return str_starts_with($header, 'DPoP ');
    }

    protected function authenticateViaDPoP(Request $request)
    {
        // TODO this is probably naive, we should validate the signatures, check DPoP header, etc.
        $jwt = JWT::parse(substr($request->header('Authorization'), 5));
        $clientId = $jwt->claims()->get('aud');

        if (is_array($clientId)) {
            $clientId = $clientId[0] ?? null;
        }

        $userId = $jwt->claims()->get('sub');
        $accessToken = $jwt->claims()->get('jti');

        // From this point forward, the code is mostly replicated from the parent's
        // authenticateViaBearerToken() implementation.
        $client = $this->clients->findActive($clientId);

        if (! $client ||
            ($client->provider &&
             $client->provider !== $this->provider->getProviderName())) {
            return;
        }

        // If the access token is valid we will retrieve the user according to the user ID
        // associated with the token. We will use the provider implementation which may
        // be used to retrieve users from Eloquent. Next, we'll be ready to continue.
        $user = $this->provider->retrieveById($userId);

        if (! $user) {
            return;
        }

        // Next, we will assign a token instance to this user which the developers may use
        // to determine if the token has a given scope, etc. This will be useful during
        // authorization such as within the developer's Laravel model policy classes.
        $token = Token::find($accessToken);

        if ($token) {
            $accessTokenInstance = new AccessToken([
                'oauth_access_token_id' => $token->id,
                'oauth_scopes' => $token->scopes,
                'oauth_user_id' => $token->user_id,
                'oauth_client_id' => $token->client_id,
            ]);

            return $user->withAccessToken($accessTokenInstance);
        }

        return null;
    }
}
