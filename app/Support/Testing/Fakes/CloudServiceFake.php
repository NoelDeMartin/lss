<?php

namespace App\Support\Testing\Fakes;

use App\Models\User;
use App\Services\CloudService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Testing\Fakes\Fake;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class CloudServiceFake extends CloudService implements Fake
{
    protected $filesystem;

    public function __construct()
    {
        $adapter = new InMemoryFilesystemAdapter;

        $this->filesystem = new FilesystemAdapter(new LeagueFilesystem($adapter), $adapter);
    }

    public function forUser(?User $user = null): Filesystem
    {
        return $this->filesystem;
    }

    public function assertContains(string $path, string $contents): void
    {
        expect($this->filesystem->read($path))->toContain($contents);
    }

    public function assertDoesntContain(string $path, string $contents): void
    {
        expect($this->filesystem->read($path))->not->toContain($contents);
    }
}
