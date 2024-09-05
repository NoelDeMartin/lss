<?php

namespace App\Services;

use App\Models\User;
use App\Support\Facades\Sparql;
use EasyRdf\Graph;
use EasyRdf\Serialiser\Turtle as TurtleSerializer;
use Illuminate\Contracts\Filesystem\Filesystem;

class SolidService
{
    protected $user = null;

    protected $cloud = null;

    public function syncProfile(User $user): void
    {
        if (! $user->cloud()->exists("/{$user->cloud_folder}/profile/card.ttl")) {
            $this->createProfile($user);

            return;
        }

        $this->updateProfile($user);
    }

    public function read(string $path): string
    {
        if (str_ends_with($path, '/')) {
            return $this->readContainer($path);
        }

        return $this->readDocument($path);
    }

    public function create(string $path, string $turtle): void
    {
        if (str_ends_with($path, '/')) {
            $this->createContainer($path, $turtle);

            return;
        }

        $this->createDocument($path, $turtle);
    }

    public function update(string $path, string $sparql): void
    {
        if (! $this->cloud()->exists($this->preparePath("$path.ttl"))) {
            abort(404);
        }

        $turtle = $this->cloud()->get($this->preparePath("$path.ttl"));

        $this->cloud()->put($this->preparePath("$path.ttl"), Sparql::updateTurtle($turtle, $sparql, ['base' => $path]));
    }

    protected function createProfile(User $user): void
    {
        $card = file_get_contents(resource_path('/templates/card.ttl'));
        $card = str_replace('{name}', $user->name, $card);
        $card = str_replace('{oidcIssuer}', route('home'), $card);

        $user->cloud()->put("{$user->cloud_folder}/profile/card.ttl", $card);
    }

    protected function updateProfile(User $user): void
    {
        $base = '/profile/card';
        $turtle = $user->cloud()->get("/{$user->cloud_folder}/profile/card.ttl");
        $graph = new Graph($base);
        $serialiser = new TurtleSerializer;

        $graph->parse($turtle, 'turtle');
        $this->updateGraphProperty($graph, "$base#me", 'foaf:name', $user->name);
        $user->cloud()->put("/{$user->cloud_folder}/profile/card.ttl", $serialiser->serialise($graph, 'turtle'));
    }

    protected function updateGraphProperty(Graph $graph, string $resource, string $property, string $value): void
    {
        $literals = $graph->allLiterals($resource, $property);

        foreach ($literals as $literal) {
            $graph->deleteLiteral($resource, $property, $literal->getValue());
        }

        $graph->addLiteral($resource, $property, $value);
    }

    protected function readDocument(string $path): string
    {
        if (! $this->cloud()->exists($this->preparePath("$path.ttl"))) {
            abort(404);
        }

        return $this->cloud()->get($this->preparePath("$path.ttl"));
    }

    protected function readContainer(string $path): string
    {
        $turtle = $this->cloud()->get($this->preparePath("$path.meta.ttl")) ?? '';

        if (empty($turtle) && ! $this->cloud()->exists($this->preparePath($path))) {
            abort(404);
        }

        $turtle .= '<> a <http://www.w3.org/ns/ldp#Container> .';

        foreach ($this->children($path) as $child) {
            $turtle .= "\n<> <http://www.w3.org/ns/ldp#contains> <$path$child> .";
        }

        return $turtle;
    }

    protected function createDocument(string $path, string $turtle): void
    {
        if ($this->cloud()->exists($this->preparePath("$path.ttl"))) {
            abort(409, 'Already exists');
        }

        // TODO ensure directory exists

        $this->cloud()->put($this->preparePath("$path.ttl"), $turtle);
    }

    protected function createContainer(string $path, string $turtle): void
    {
        if ($this->cloud()->exists($this->preparePath($path))) {
            abort(409, 'Already exists');
        }

        // TODO ensure directory exists

        $this->cloud()->put($this->preparePath("$path.meta.ttl"), $turtle);
    }

    protected function children(string $path): array
    {
        $files = [];

        foreach ($this->cloud()->files($this->preparePath($path)) as $file) {
            $files[] = basename($file);
        }

        foreach ($this->cloud()->directories($this->preparePath($path)) as $directory) {
            $files[] = basename($directory).'/';
        }

        $files = array_filter($files, fn ($filename) => ! str_starts_with($filename, '.'));

        return array_map(fn ($file) => str_ends_with($file, '/') ? $file : substr($file, 0, strlen($file) - 4), $files);
    }

    protected function preparePath(string $path): string
    {
        $user = $this->user();

        return "/{$user->cloud_folder}$path";
    }

    protected function user(): User
    {
        if (is_null($this->user)) {
            $this->user = User::whereUsername(request()->username())->first();

            if (! $this->user->hasCloud()) {
                abort(400, 'Cloud configuration missing.');
            }
        }

        return $this->user;
    }

    protected function cloud(): Filesystem
    {
        if (is_null($this->cloud)) {
            $this->cloud = $this->user()->cloud();
        }

        return $this->cloud;
    }
}
