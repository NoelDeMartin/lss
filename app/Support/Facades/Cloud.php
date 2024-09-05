<?php

namespace App\Support\Facades;

use App\Models\User;
use App\Support\Testing\Fakes\CloudServiceFake;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Filesystem forUser(User $user);
 *
 * @see \App\Services\CloudService
 */
class Cloud extends Facade
{
    public static function fake(): CloudServiceFake
    {
        return tap(new CloudServiceFake, function ($fake) {
            static::swap($fake);
        });
    }

    protected static function getFacadeAccessor()
    {
        return 'cloud';
    }
}
