<?php

namespace App\Services;

use Laravel\Passport\Passport;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\CryptKey;
use Strobotti\JWK\KeyFactory;

class JWTService
{
    private $config = null;

    public function parse(string $jwt): UnencryptedToken
    {
        $parser = new Parser(new JoseEncoder);
        $token = $parser->parse($jwt);

        assert($token instanceof UnencryptedToken);

        return $token;
    }

    public function build(): Builder
    {
        return $this->config()->builder();
    }

    public function signer(): Signer
    {
        return $this->config()->signer();
    }

    public function signingKey(): Key
    {
        return $this->config()->signingKey();
    }

    public function jwk(): object
    {
        $publicKey = $this->makeCryptKey('public');
        $keyFactory = new KeyFactory;
        $jwk = $keyFactory->createFromPem($publicKey->getKeyContents(), [
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => 'lss', // TODO this should change every time the keys are rotated.
        ]);

        return json_decode($jwk);
    }

    protected function config(): Configuration
    {
        if (is_null($this->config)) {
            $privateKey = $this->makeCryptKey('private');

            $this->config = Configuration::forAsymmetricSigner(
                new Sha256,
                InMemory::plainText($privateKey->getKeyContents(), $privateKey->getPassPhrase() ?? ''),
                InMemory::plainText('empty', 'empty')
            );
        }

        return $this->config;
    }

    protected function makeCryptKey(string $type): CryptKey
    {
        // Code copied from Laravel\Passport\PassportServiceProvider.
        $key = str_replace('\\n', "\n", config('passport.'.$type.'_key') ?? '');

        if (! $key) {
            $key = 'file://'.Passport::keyPath('oauth-'.$type.'.key');
        }

        return new CryptKey($key, null, Passport::$validateKeyPermissions && ! windows_os());
    }
}
