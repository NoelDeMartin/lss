<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PodStorageManager
{
    public function read(string $path): ?string
    {
        $path = storage_path("pod$path");

        if (! File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    public function list(string $path): array
    {
        $files = $this->listFiles($path);

        return array_map(fn ($file) => substr($file, 0, strlen($file) - 4), $files);
    }

    public function write(string $path, string $contents): void
    {
        $path = storage_path("pod$path");

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    protected function listFiles(string $path): array
    {
        $path = storage_path("pod$path");
        $files = [];

        foreach (File::files($path) as $file) {
            $files[] = $file->getFilename();
        }

        return $files;
    }
}
