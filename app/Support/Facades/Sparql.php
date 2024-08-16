<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string updateTurtle(string $turtle, string $update, ?array $options);
 *
 * @see \App\Services\SparqlService
 */
class Sparql extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sparql';
    }
}
