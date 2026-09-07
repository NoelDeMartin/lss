<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnsafeUrlException extends HttpException
{
    public static function malformed(): self
    {
        return new self(502, 'The url is malformed.');
    }

    public static function scheme(string $scheme): self
    {
        return new self(502, "The '{$scheme}' scheme is not allowed, only http and https urls are supported.");
    }

    public static function unresolvable(string $host): self
    {
        return new self(502, "The host '{$host}' could not be resolved.");
    }

    public static function privateAddress(string $host, string $ip): self
    {
        return new self(502, "The host '{$host}' resolves to '{$ip}', which is not a public address.");
    }
}
