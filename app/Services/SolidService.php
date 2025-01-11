<?php

namespace App\Services;

use App\Models\User;
use App\Support\Facades\Sparql;
use App\Support\Serializers\TurtleSerializer;
use EasyRdf\Graph;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;

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
        $turtle = str_ends_with($path, '/') ? $this->readContainer($path) : $this->readDocument($path);

        if (is_null($turtle)) {
            abort(404);
        }

        return $turtle;
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
        if (! $this->pathExists("$path.ttl")) {
            abort(404);
        }

        $turtle = $this->cloud()->get($this->preparePath("$path.ttl"));

        $this->cloud()->put(
            $this->preparePath("$path.ttl"),
            Sparql::updateTurtle($turtle, $sparql, [
                'base' => $this->user()->url(),
                'document' => $this->user()->url($path),
            ]),
        );
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
        $base = $user->url('/profile/card');
        $turtle = $user->cloud()->get("/{$user->cloud_folder}/profile/card.ttl");
        $graph = new Graph($base);
        $serialiser = new TurtleSerializer;

        $graph->parse($turtle, 'turtle');
        $this->updateGraphProperty($graph, "$base#me", 'foaf:name', $user->name);
        $user->cloud()->put(
            "/{$user->cloud_folder}/profile/card.ttl",
            $serialiser->serialise($graph, 'turtle', [
                'implicit_base' => $user->url(),
                'implicit_document' => $user->url('/profile/card'),
            ]),
        );
    }

    protected function updateGraphProperty(Graph $graph, string $resource, string $property, string $value): void
    {
        $literals = $graph->allLiterals($resource, $property);

        foreach ($literals as $literal) {
            $graph->deleteLiteral($resource, $property, $literal->getValue());
        }

        $graph->addLiteral($resource, $property, $value);
    }

    protected function readDocument(string $path): ?string
    {
        try {
            $turtle = $this->cloud()->get($this->preparePath("$path.ttl"));

            if (empty($turtle) && ! $this->pathExists("$path.ttl")) {
                return null;
            }

            return $turtle ?? '';
        } catch (UnableToReadFile $e) {
            return null;
        }
    }

    protected function readContainer(string $path): ?string
    {
        $turtle = $this->readDocument("$path.meta");

        if (is_null($turtle) && ! $this->pathExists($path)) {
            return null;
        }

        $turtle ??= '';
        $turtle .= "\n<> a <http://www.w3.org/ns/ldp#Container> .";

        $date = now();
        foreach ($this->children($path) as $child) {
            $name = $child['name'];
            $lastModifiedTime = $child['last_modified'];

            $turtle .= "\n<> <http://www.w3.org/ns/ldp#contains> <$path$name> .";

            if (! is_null($lastModifiedTime)) {
                $lastModifiedDate = $date->setTimestamp($child['last_modified'])->toISOString();

                $turtle .= "\n<$path$name> <http://purl.org/dc/terms/modified> \"$lastModifiedDate\"^^<http://www.w3.org/2001/XMLSchema#dateTime> .";
                $turtle .= "\n<$path$name> <http://www.w3.org/ns/posix/stat#modified> $lastModifiedTime .";
            }
        }

        return $turtle;
    }

    protected function createDocument(string $path, string $turtle): void
    {
        if ($this->pathExists("$path.ttl")) {
            abort(409, 'Already exists');
        }

        // TODO ensure directory exists

        $this->cloud()->put($this->preparePath("$path.ttl"), $turtle);
    }

    protected function createContainer(string $path, string $turtle): void
    {
        if ($this->pathExists($path)) {
            abort(409, 'Already exists');
        }

        // TODO ensure directory exists

        $this->cloud()->put($this->preparePath("$path.meta.ttl"), $turtle);
    }

    protected function pathExists($path): bool
    {
        try {
            return $this->cloud()->exists($this->preparePath($path));
        } catch (UnableToReadFile) {
            return false;
        }
    }

    protected function children(string $path): array
    {
        $children = [];
        $files = $this->cloud()->getDriver()->listContents($this->preparePath($path));

        foreach ($files as $i => $file) {
            $filename = basename($file->path());

            if (str_starts_with($filename, '.')) {
                continue;
            }

            if ($file->isDir()) {
                $filename .= '/';
            } else {
                $filename = substr($filename, 0, strlen($filename) - 4);
            }

            $children[] = [
                'name' => $filename,
                'last_modified' => $file->lastModified(),
            ];
        }

        return $children;
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

            if (is_null($this->user)) {
                abort(404);
            }

            if (! $this->user->hasCloud()) {
                abort(400, 'Cloud configuration missing.');
            }
        }

        return $this->user;
    }

    protected function cloud(): FilesystemAdapter
    {
        if (is_null($this->cloud)) {
            $this->cloud = $this->user()->cloud();
        }

        return $this->cloud;
    }
}
