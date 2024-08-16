<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function putTurtle(string $path, string $turtle)
    {
        $server = $this->transformHeadersToServerVars(['Content-Type' => 'text/turtle']);

        return $this->call('PUT', $path, [], [], [], $server, $turtle);
    }

    public function sparqlUpdate(string $path, string $sparql)
    {
        $server = $this->transformHeadersToServerVars(['Content-Type' => 'application/sparql-update']);

        return $this->call('PATCH', $path, [], [], [], $server, $sparql);
    }

    protected function prepareUrlForRequest($uri)
    {
        // Override to avoid removing trailing slashes.
        return $uri;
    }
}
