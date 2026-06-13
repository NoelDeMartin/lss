<?php

namespace App\Support\Facades;

use App\Models\User;
use App\Services\SolidService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void syncProfile(User $user);
 * @method static array read(string $path);
 * @method static array create(string $path, string $content, array $options = []);
 * @method static void update(string $path, string $sparql);
 *
 * @see SolidService
 */
class Solid extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'solid';
    }
}
