<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\HandleCors as FrameworkHandleCors;
use Illuminate\Http\Request;

class HandleCors extends FrameworkHandleCors
{
    protected function hasMatchingPath(Request $request): bool
    {
        // Override default behavior to exclude indicated paths, instead of enabling them.
        $paths = $this->container['config']->get('cors.exclude', []);

        foreach ($paths as $path) {
            if (! $request->fullUrlIs($path) && ! $request->is($path)) {
                continue;
            }

            return false;
        }

        return true;
    }
}
