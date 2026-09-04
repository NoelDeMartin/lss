<?php

namespace App\Support\Facades;

use App\Models\User;
use App\Services\SolidService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void syncProfile(User $user)
 * @method static array{content: string, mime_type: string, last_modified: \Carbon\CarbonInterface} read(string $path)
 * @method static array{status: int} create(string $path, string $content, array<string, mixed> $options = [])
 * @method static void update(string $path, string $sparql)
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
