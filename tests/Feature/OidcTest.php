<?php

use App\Models\Client;
use App\Models\User;
use App\Support\Facades\Cloud;
use App\Support\Facades\JWT;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Token;

test('OIDC flow', function () {
    // Register client.
    $response = $this->post('/.oidc/register', [
        'client_name' => 'Umai',
        'redirect_uris' => ['https://umai.noeldemartin.com'],
    ]);

    $response->assertStatus(201);
    $response->assertJson(fn (AssertableJson $json) => $json->hasAll(['client_id', 'client_name', 'redirect_uris']));

    $clientId = $response->json('client_id');
    $redirectUri = $response->json('redirect_uris')[0];

    // Request code.
    $user = User::factory()->create();
    $username = $user->username;
    $state = Str::random(40);
    $codeVerifier = Str::random(128);
    $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');
    $query = http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'state' => $state,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
    ]);
    $response = $this->actingAs($user, 'web')->get("/.oidc/authorize?$query");

    $response->assertRedirect();

    $url = parse_url($response->headers->get('Location'));
    parse_str($url['query'], $query);
    $code = $query['code'];

    // Request token.
    $response = $this->post('/.oidc/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'code' => $code,
        'code_verifier' => $codeVerifier,
    ]);

    $response->assertStatus(200);
    $response->assertJson(fn (AssertableJson $json) => $json->hasAll(['id_token', 'token_type', 'expires_in', 'access_token', 'refresh_token']));

    $token = JWT::parse($response->json('id_token'));
    expect($token->isRelatedTo("http://$username.localhost/profile/card#me"))->toBeTrue();
});

it('exposes public keys', function () {
    $response = $this->get('/.oidc/jwks');

    $response->assertStatus(200);
    $response->assertJson(fn (AssertableJson $json) => $json->hasAll(['keys']));
});

it('uses DPoP headers to authenticate', function () {
    Cloud::fake();

    $user = User::factory()->nextcloud()->create();
    $client = Client::factory()->create();
    $token = Token::create([
        'id' => Str::random(),
        'user_id' => $user->id,
        'client_id' => $client->id,
        'revoked' => false,
    ]);
    $jwt = JWT::build()
        ->identifiedBy($token->id)
        ->relatedTo($user->id)
        ->permittedFor($client->id)
        ->getToken(JWT::signer(), JWT::signingKey())
        ->toString();
    $response = $this
        ->forUserDomain($user)
        ->withHeader('Authorization', "DPoP $jwt")
        ->putTurtle('/settings/privateTypeIndex', '<> a <http://www.w3.org/ns/solid/terms#TypeIndex> .');

    $response->assertStatus(201);
});
