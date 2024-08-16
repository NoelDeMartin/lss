<?php

namespace App\Support\Facades;

use App\Support\Testing\Fakes\PodStorageManagerFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null read(string $path);
 * @method static string[] list(string $path);
 * @method static void write(string $path, string $contents);
 *
 * @see \App\Services\PodStorageManager
 */
class PodStorage extends Facade
{
    public static function fake(): PodStorageManagerFake
    {
        return tap(new PodStorageManagerFake, function ($fake) {
            static::swap($fake);
        });
    }

    protected static function getFacadeAccessor()
    {
        return 'pod-storage';
    }
}
