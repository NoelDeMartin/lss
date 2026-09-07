<?php

namespace App\Support;

use Sabre\DAV\Client;

class CloudClient extends Client
{
    /**
     * Redirects are not followed because only the original host has been
     * validated and pinned, so a redirect could point requests anywhere.
     *
     * @var int
     */
    protected $maxRedirects = 0;

    public function __construct(PublicUrl $url, string $username, string $password)
    {
        parent::__construct([
            'baseUri' => $url->url,
            'userName' => $username,
            'password' => $password,
        ]);

        $pinnedAddresses = $url->pinnedAddresses();

        if (! is_null($pinnedAddresses)) {
            $this->addCurlSetting(CURLOPT_RESOLVE, [$pinnedAddresses]);
        }
    }
}
