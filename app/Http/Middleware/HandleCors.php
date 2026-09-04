<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\HandleCors as FrameworkHandleCors;
use Illuminate\Http\Request;

class HandleCors extends FrameworkHandleCors
{
    protected function hasMatchingPath(Request $request): bool
    {
        // Override default behavior to exclude indicated paths, instead of enabling them.
        /** @var array<string> $paths */
        $paths = config('cors.exclude', []);

        foreach ($paths as $path) {
            if (! $request->fullUrlIs($path) && ! $request->is($path)) {
                continue;
            }

            return false;
        }

        return true;
    }
}
