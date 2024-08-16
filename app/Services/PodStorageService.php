<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PodStorageService
{
    public function read(string $path): ?string
    {
        $path = storage_path("data$path");

        if (! File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    public function list(string $path): array
    {
        $files = $this->listFiles($path);

        return array_map(fn ($file) => str_ends_with($file, '/') ? $file : substr($file, 0, strlen($file) - 4), $files);
    }

    public function write(string $path, string $contents): void
    {
        $path = storage_path("data$path");

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    protected function listFiles(string $path): array
    {
        $path = storage_path("data$path");
        $files = [];

        foreach (File::files($path) as $file) {
            $files[] = $file->getFilename();
        }

        foreach (File::directories($path) as $directory) {
            $files[] = basename($directory).'/';
        }

        return $files;
    }
}
