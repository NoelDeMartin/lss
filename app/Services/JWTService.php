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
    private ?Configuration $config = null;

    public function parse(string $jwt): UnencryptedToken
    {
        /** @var non-empty-string $jwtString */
        $jwtString = $jwt;
        $parser = new Parser(new JoseEncoder);
        $token = $parser->parse($jwtString);

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

        $decoded = json_decode($jwk);

        return is_object($decoded) ? $decoded : (object) $decoded;
    }

    protected function config(): Configuration
    {
        if (is_null($this->config)) {
            $privateKey = $this->makeCryptKey('private');
            /** @var non-empty-string $keyContents */
            $keyContents = $privateKey->getKeyContents();

            $this->config = Configuration::forAsymmetricSigner(
                new Sha256,
                InMemory::plainText($keyContents, $privateKey->getPassPhrase() ?? ''),
                InMemory::plainText('empty', 'empty')
            );
        }

        return $this->config;
    }

    protected function makeCryptKey(string $type): CryptKey
    {
        // Code copied from Laravel\Passport\PassportServiceProvider.
        /** @var string|null $passportKey */
        $passportKey = config('passport.' . $type . '_key');
        $key = is_string($passportKey) ? str_replace('\\n', "\n", $passportKey) : '';

        if (! $key) {
            $key = 'file://' . Passport::keyPath('oauth-' . $type . '.key');
        }

        return new CryptKey($key, null, Passport::$validateKeyPermissions && ! windows_os());
    }
}
