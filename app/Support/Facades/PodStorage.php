<?php

namespace App\Support\Facades;

use App\Support\Testing\Fakes\PodStorageServiceFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null read(string $path);
 * @method static string[] list(string $path);
 * @method static void write(string $path, string $contents);
 *
 * @see \App\Services\PodStorageService
 */
class PodStorage extends Facade
{
    public static function fake(): PodStorageServiceFake
    {
        return tap(new PodStorageServiceFake, function ($fake) {
            static::swap($fake);
        });
    }

    protected static function getFacadeAccessor()
    {
        return 'pod-storage';
    }
}
