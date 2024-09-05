<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;

class PodStorageService
{
    protected $user = null;

    protected $cloud = null;

    public function init(User $user): void
    {
        if ($user->cloud()->exists("/{$user->cloud_folder}/profile/card.ttl")) {
            return;
        }

        $card = file_get_contents(resource_path('/templates/card.ttl'));
        $card = str_replace('{name}', $user->name, $card);
        $card = str_replace('{oidcIssuer}', route('home'), $card);

        $user->cloud()->put("{$user->cloud_folder}/profile/card.ttl", $card);
    }

    public function read(string $path): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        return $this->cloud()->get($this->preparePath($path));
    }

    public function list(string $path): array
    {
        $files = [];

        foreach ($this->cloud()->files($this->preparePath($path)) as $file) {
            $files[] = basename($file);
        }

        foreach ($this->cloud()->directories($this->preparePath($path)) as $directory) {
            $files[] = basename($directory).'/';
        }

        $files = array_filter($files, fn ($filename) => ! str_starts_with($filename, '.'));

        return array_map(fn ($file) => str_ends_with($file, '/') ? $file : substr($file, 0, strlen($file) - 4), $files);
    }

    public function write(string $path, string $contents): void
    {
        $this->cloud()->put($this->preparePath($path), $contents);
    }

    public function exists(string $path): bool
    {
        return $this->cloud()->exists($this->preparePath($path));
    }

    protected function preparePath(string $path): string
    {
        $user = $this->user();

        return "/{$user->cloud_folder}$path";
    }

    protected function user(): User
    {
        if (is_null($this->user)) {
            $this->user = User::whereUsername(request()->username())->first();

            if (! $this->user->hasCloud()) {
                abort(400, 'Cloud configuration missing.');
            }
        }

        return $this->user;
    }

    protected function cloud(): Filesystem
    {
        if (is_null($this->cloud)) {
            $this->cloud = $this->user()->cloud();
        }

        return $this->cloud;
    }
}
