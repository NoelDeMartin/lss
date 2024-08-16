<?php

namespace App\Support\Testing\Fakes;

use App\Services\PodStorageService;
use Illuminate\Support\Testing\Fakes\Fake;

class PodStorageServiceFake extends PodStorageService implements Fake
{
    private $files = [];

    public function read(string $path): ?string
    {
        return $this->files[$path] ?? null;
    }

    public function write(string $path, string $contents): void
    {
        $this->files[$path] = $contents;
    }

    public function assertContains(string $path, string $contents): void
    {
        expect($this->read($path))->toContain($contents);
    }

    protected function listFiles(string $path): array
    {
        $children = array_filter(array_keys($this->files), fn ($file) => str_starts_with($file, $path));
        $files = [];

        foreach (array_keys($this->files) as $file) {
            if (! str_starts_with($file, $path)) {
                continue;
            }

            $relative = substr($file, strlen($path));

            if (str_starts_with($relative, '.')) {
                continue;
            }

            $slash = stripos($relative, '/');

            $files[] = $slash !== false ? substr($relative, 0, $slash + 1) : $relative;
        }

        return array_unique($files);
    }
}
