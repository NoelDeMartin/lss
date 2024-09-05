<?php

namespace App\Support\Facades;

use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void init(User $user);
 * @method static string|null read(string $path);
 * @method static string[] list(string $path);
 * @method static void write(string $path, string $contents);
 * @method static bool exists(string $path);
 *
 * @see \App\Services\PodStorageService
 */
class PodStorage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pod-storage';
    }
}
