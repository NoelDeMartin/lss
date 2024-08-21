<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\UnencryptedToken;

/**
 * @method static UnencryptedToken parse(string $jwt);
 * @method static Builder build();
 * @method static Signer signer();
 * @method static Key signingKey();
 * @method static object jwk();
 *
 * @see \App\Services\JWTService
 */
class JWT extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'jwt';
    }
}
