<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

class PodStorageService
{
    protected $cloud = null;

    public function read(string $path): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        return $this->cloud()->get($path);
    }

    public function list(string $path): array
    {
        $files = $this->listFiles($path);

        return array_map(fn ($file) => str_ends_with($file, '/') ? $file : substr($file, 0, strlen($file) - 4), $files);
    }

    public function write(string $path, string $contents): void
    {
        $this->cloud()->put($path, $contents);
    }

    public function exists(string $path): bool
    {
        return $this->cloud()->exists($path);
    }

    protected function listFiles(string $path): array
    {
        $files = [];

        foreach ($this->cloud()->files($path) as $file) {
            $files[] = basename($file);
        }

        foreach ($this->cloud()->directories($path) as $directory) {
            $files[] = basename($directory).'/';
        }

        return array_filter($files, fn ($filename) => ! str_starts_with($filename, '.'));
    }

    protected function cloud(): Filesystem
    {
        if (is_null($this->cloud)) {
            // TODO fix this for multi-tenancy.
            $user = User::first();
            $folder = 'Solid';
            $config = [
                'baseUri' => $user->nextcloud_url,
                'userName' => $user->nextcloud_username,
                'password' => $user->nextcloud_password,
                'throw' => app()->hasDebugModeEnabled(),
            ];
            $client = new Client($config);
            $adapter = new WebDAVAdapter($client, "remote.php/dav/files/{$user->nextcloud_username}/$folder/");

            $this->cloud = new FilesystemAdapter(
                new LeagueFilesystem($adapter, $config),
                $adapter,
                $config
            );
        }

        return $this->cloud;
    }
}
