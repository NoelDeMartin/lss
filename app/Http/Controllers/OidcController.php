<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Support\Facades\JWT;
use Illuminate\Http\Response;
use Laravel\Passport\ClientRepository;

class OidcController extends Controller
{
    public function register(ClientRequest $request, ClientRepository $clients): Response
    {
        /** @var string $name */
        $name = $request->validated('client_name');
        /** @var array<string> $redirectUris */
        $redirectUris = (array) $request->validated('redirect_uris');

        /** @var Client|null $client */
        $client = Client::where('name', $name)
            ->where('revoked', false)
            ->get()
            ->first(function (Client $client) use ($redirectUris) {
                /** @var array<string>|null $clientUris */
                $clientUris = $client->getAttribute('redirect_uris');

                return ! is_null($clientUris)
                    && collect($clientUris)->diff($redirectUris)->isEmpty()
                    && collect($redirectUris)->diff($clientUris)->isEmpty();
            });

        if (! $client) {
            $client = $clients->createAuthorizationCodeGrantClient($name, $redirectUris, false);
        }

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
