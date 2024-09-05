<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

class CloudService
{
    public function forUser(User $user): Filesystem
    {
        $config = [
            'baseUri' => $user->nextcloud_url,
            'userName' => $user->nextcloud_username,
            'password' => $user->nextcloud_password,
            'throw' => app()->hasDebugModeEnabled(),
        ];
        $client = new Client($config);
        $adapter = new WebDAVAdapter($client, "remote.php/dav/files/{$user->nextcloud_username}/");

        return new FilesystemAdapter(
            new LeagueFilesystem($adapter, $config),
            $adapter,
            $config
        );
    }
}
