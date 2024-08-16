<?php

namespace App\Support\Testing\Fakes;

use App\Services\PodStorageManager;
use Illuminate\Support\Testing\Fakes\Fake;

class PodStorageManagerFake extends PodStorageManager implements Fake
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
        return array_map(
            'basename',
            array_filter(
                array_keys($this->files),
                fn ($file) => str_starts_with($file, $path) && ! str_starts_with($file, "$path.")
            )
        );
    }
}
