<?php

namespace App\Services;

use App\Exceptions\UnsafeUrlException;
use App\Models\User;
use App\Support\CloudClient;
use App\Support\PublicUrl;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;

class CloudService
{
    /**
     * @throws UnsafeUrlException
     */
    public function forUser(User $user): FilesystemAdapter
    {
        $url = PublicUrl::resolve((string) $user->nextcloud_url);
        $config = ['throw' => app()->hasDebugModeEnabled()];
        $client = new CloudClient($url, (string) $user->nextcloud_username, (string) $user->nextcloud_password);
        $adapter = new WebDAVAdapter($client, "remote.php/dav/files/{$user->nextcloud_username}/");

        return new FilesystemAdapter(
            new LeagueFilesystem($adapter, $config),
            $adapter,
            $config
        );
    }
}
