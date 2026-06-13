<?php

namespace App\Support\Facades;

use App\Services\SparqlService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string updateTurtle(string $turtle, string $update, ?array $options);
 *
 * @see SparqlService
 */
class Sparql extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sparql';
    }
}
