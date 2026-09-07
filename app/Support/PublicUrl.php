<?php

namespace App\Support;

use App\Exceptions\UnsafeUrlException;

/**
 * A url that has been verified to point to a host on the public internet.
 *
 * Instances can only be created through `resolve()`, so any code that receives
 * a PublicUrl can trust that the scheme and every IP address behind the hostname
 * have been checked. The resolved addresses are kept so that the HTTP client can
 * pin the connection to them instead of resolving the hostname a second time
 * (which would be vulnerable to DNS rebinding).
 *
 * When the `cloud.allow_private_hosts` option is enabled (e.g. for local
 * development or running in a private network), hostnames are not resolved nor
 * checked, and connections are not pinned.
 */
final readonly class PublicUrl
{
    public const ALLOWED_SCHEMES = ['http', 'https'];

    /**
     * @param  list<string>  $ips
     */
    private function __construct(
        public string $url,
        public string $host,
        public int $port,
        public array $ips,
    ) {
        //
    }

    /**
     * @throws UnsafeUrlException
     */
    public static function resolve(string $url, ?DnsResolver $dns = null): self
    {
        $parts = parse_url($url);
        $scheme = is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';
        $host = is_array($parts) ? ($parts['host'] ?? '') : '';

        if ($scheme === '' || $host === '') {
            throw UnsafeUrlException::malformed();
        }

        if (! in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            throw UnsafeUrlException::scheme($scheme);
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (config()->boolean('cloud.allow_private_hosts')) {
            return new self($url, $host, $port, []);
        }

        $ips = self::resolveHost($host, $dns ?? app(DnsResolver::class));

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) {
                throw UnsafeUrlException::privateAddress($host, $ip);
            }
        }

        return new self($url, $host, $port, $ips);
    }

    /**
     * @return list<string>
     */
    private static function resolveHost(string $host, DnsResolver $dns): array
    {
        $literal = trim($host, '[]');

        if (filter_var($literal, FILTER_VALIDATE_IP) !== false) {
            return [$literal];
        }

        $ips = $dns->resolve($host);

        if ($ips === []) {
            throw UnsafeUrlException::unresolvable($host);
        }

        return $ips;
    }

    /**
     * Build a cURL --resolve entry so the connection goes to the addresses that
     * were validated, instead of resolving the hostname again.
     */
    public function pinnedAddresses(): ?string
    {
        if ($this->ips === []) {
            return null;
        }

        $ips = array_map(
            fn (string $ip) => str_contains($ip, ':') ? "[{$ip}]" : $ip,
            $this->ips,
        );

        return "{$this->host}:{$this->port}:" . implode(',', $ips);
    }
}
