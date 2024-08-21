<?php

namespace App\Auth\Guards;

use App\Support\Facades\JWT;
use Illuminate\Http\Request;
use Laravel\Passport\Guards\TokenGuard as BaseTokenGuard;

class TokenGuard extends BaseTokenGuard
{
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        if ($this->usingDPoP($this->request)) {
            return $this->user = $this->authenticateViaDPoP($this->request);
        }

        return parent::user();
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
        $token = $this->tokens->find($accessToken);

        // @phpstan-ignore ternary.alwaysTrue
        return $token ? $user->withAccessToken($token) : null;
    }
}
