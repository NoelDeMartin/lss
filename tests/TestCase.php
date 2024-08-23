<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $username = null;

    public function forUser(User $user)
    {
        $this->username = $user->username;

        return $this;
    }

    public function authenticated()
    {
        $user = User::factory()->create();

        return $this->forUser($user)->actingAs($user, 'solid');
    }

    public function readTurtle(string $path)
    {
        $this->prepareServerVariables();

        return $this->get($path, ['Accept' => 'text/turtle']);
    }

    public function putTurtle(string $path, string $turtle)
    {
        $this->prepareServerVariables();

        $server = $this->transformHeadersToServerVars(['Content-Type' => 'text/turtle']);

        return $this->call('PUT', $path, [], [], [], $server, $turtle);
    }

    public function sparqlUpdate(string $path, string $sparql)
    {
        $this->prepareServerVariables();

        $server = $this->transformHeadersToServerVars(['Content-Type' => 'application/sparql-update']);

        return $this->call('PATCH', $path, [], [], [], $server, $sparql);
    }

    protected function prepareServerVariables()
    {
        if (is_null($this->username)) {
            return;
        }

        $this->withServerVariables([
            'SERVER_NAME' => "http://{$this->username}.localhost",
            'HTTP_HOST' => "{$this->username}.localhost",
        ]);
    }

    protected function prepareUrlForRequest($uri)
    {
        // Override to avoid removing trailing slashes.
        return $uri;
    }
}
