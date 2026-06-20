<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Support\Facades\JWT;
use Laravel\Passport\ClientRepository;

class OidcController extends Controller
{
    public function register(ClientRequest $request, ClientRepository $clients)
    {
        // TODO a new client is created each time users log in, we should probably do something
        // to reuse existing clients.
        $name = $request->input('client_name');
        $client = $clients->createAuthorizationCodeGrantClient($name, $request->input('redirect_uris'), false);

        return response([
            'client_id' => $client->id,
            'client_name' => $client->name,
            'redirect_uris' => $client->getAttribute('redirect_uris'),
        ], 201);
    }

    public function jwks()
    {
        return ['keys' => [JWT::jwk()]];
    }
}
