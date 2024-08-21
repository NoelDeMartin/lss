<?php

use App\Http\Controllers\OidcController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::withoutMiddleware([VerifyCsrfToken::class])->group(function () {
    Route::get('.well-known/openid-configuration', function () {
        return [
            'issuer' => route('home'),
            'authorization_endpoint' => route('passport.authorizations.authorize'),
            'token_endpoint' => route('passport.token'),
            'jwks_uri' => route('oidc.jwks'),
            'registration_endpoint' => route('oidc.register'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['ES256'],
        ];
    })->name('oidc.config');
    Route::post('.oidc/register', [OidcController::class, 'register'])->name('oidc.register');
    Route::get('.oidc/jwks', [OidcController::class, 'jwks'])->name('oidc.jwks');
});
