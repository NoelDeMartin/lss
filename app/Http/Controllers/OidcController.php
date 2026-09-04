<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Support\Facades\JWT;
use Illuminate\Http\Response;
use Laravel\Passport\ClientRepository;

class OidcController extends Controller
{
    public function register(ClientRequest $request, ClientRepository $clients): Response
    {
        // TODO a new client is created each time users log in, we should probably do something
        // to reuse existing clients.
        /** @var string $name */
        $name = $request->input('client_name');
        /** @var array<string> $redirectUris */
        $redirectUris = (array) $request->input('redirect_uris');
        $client = $clients->createAuthorizationCodeGrantClient($name, $redirectUris, false);

        return response([
            'client_id' => $client->id,
            'client_name' => $client->name,
            'redirect_uris' => $client->getAttribute('redirect_uris'),
        ], 201);
    }

    /**
     * @return array{keys: array<int, object>}
     */
    public function jwks(): array
    {
        return ['keys' => [JWT::jwk()]];
    }
}
