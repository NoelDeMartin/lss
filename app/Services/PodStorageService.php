<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;

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
            $user = User::whereUsername(request()->username())->first();

            if (! $user->hasCloud()) {
                abort(400, 'Cloud configuration missing.');
            }

            $this->cloud = $user->cloud();
        }

        return $this->cloud;
    }
}
