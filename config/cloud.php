<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow Private Hosts
    |--------------------------------------------------------------------------
    |
    | Nextcloud urls are only accepted when they point to a host on the public
    | internet, so that this server can't be used to reach internal services
    | (SSRF). You may enable this option to allow private and loopback hosts,
    | such as "localhost" or a LAN address, when developing locally or running
    | in a private network.
    |
    */

    'allow_private_hosts' => env('CLOUD_ALLOW_PRIVATE_HOSTS', false),

    /*
    |--------------------------------------------------------------------------
    | DNS Cache TTL
    |--------------------------------------------------------------------------
    |
    | Nextcloud hostnames are resolved every time a connection is opened, to
    | check that they still point to public addresses. To avoid paying for
    | a DNS lookup on every request, the resolved addresses are cached for
    | this amount of seconds. Set this to 0 to disable caching entirely.
    |
    */

    'dns_cache_ttl' => env('CLOUD_DNS_CACHE_TTL', 300),

];
