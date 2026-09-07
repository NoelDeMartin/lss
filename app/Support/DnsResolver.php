<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class DnsResolver
{
    /**
     * Resolve both IPv4 and IPv6 addresses for the given hostname, caching
     * the result according to the configured TTL.
     *
     * @return list<string>
     */
    public function resolve(string $host): array
    {
        $ttl = config()->integer('cloud.dns_cache_ttl');

        if ($ttl <= 0) {
            return $this->lookup($host);
        }

        $key = 'dns:' . strtolower($host);

        /** @var list<string>|null $cached */
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $ips = $this->lookup($host);

        // Failed lookups are not cached, so that a host that becomes resolvable
        // (or a typo that gets fixed) doesn't keep failing until the TTL expires.
        if ($ips !== []) {
            Cache::put($key, $ips, $ttl);
        }

        return $ips;
    }

    /**
     * Query the DNS for both IPv4 and IPv6 addresses, bypassing the cache.
     *
     * @return list<string>
     */
    public function lookup(string $host): array
    {
        // dns_get_record() emits a warning (which Laravel turns into an exception)
        // when the DNS server fails, treat that the same as an unresolvable host.
        $records = rescue(fn () => @dns_get_record($host, DNS_A | DNS_AAAA), [], report: false);

        if (! is_array($records)) {
            return [];
        }

        $ips = [];

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (is_string($ip)) {
                $ips[] = $ip;
            }
        }

        return $ips;
    }
}
