<?php

namespace App\Services;

use App\Models\User;
use App\Support\Facades\Sparql;
use App\Support\Serializers\TurtleSerializer;
use EasyRdf\Graph;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\Mime\MimeTypes;

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

    public function read(string $path): array
    {
        $response = str_ends_with($path, '/') ? $this->readContainer($path) : $this->readDocument($path);

        if (is_null($response)) {
            abort(404);
        }

        return $response;
    }

    public function create(string $path, string $content, array $options = []): array
    {
        if (str_ends_with($path, '/')) {
            return $this->createContainer($path, $content, $options);
        }

        return $this->createDocument($path, $content, $options);
    }

    public function update(string $path, string $sparql): void
    {
        if (! $this->filePathExists($path)) {
            abort(404);
        }

        $content = $this->cloud()->get($this->prepareFilePath($path));

        $this->cloud()->put(
            $this->prepareFilePath($path),
            Sparql::updateTurtle($content, $sparql, [
                'base' => $this->user()->url(),
                'document' => $this->user()->url($path),
            ]),
        );
    }

    protected function getExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
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
        $this->updateGraphProperty($graph, "{$base}#me", 'foaf:name', $user->name);
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

    protected function readDocument(string $path): ?array
    {
        try {
            $filePath = $this->prepareFilePath($path);
            $content = $this->cloud()->get($filePath);

            if (empty($content) && ! $this->filePathExists($path)) {
                return null;
            }

            return [
                'content' => $content,
                'mime_type' => MimeTypes::getDefault()->getMimeTypes($this->getExtension($path))[0] ?? 'text/turtle',
                'last_modified' => Carbon::createFromTimestamp($this->cloud()->lastModified($filePath)),
            ];
        } catch (UnableToReadFile) {
            return null;
        }
    }

    protected function readContainer(string $path): ?array
    {
        $response = $this->readDocument("{$path}.meta");

        if (is_null($response) && ! $this->pathExists($path)) {
            return null;
        }

        $lastModified = Carbon::createFromTimestamp($this->cloud()->lastModified($this->preparePath($path)));
        $turtle = $response['content'] ?? '';
        $turtle .= "\n<> a <http://www.w3.org/ns/ldp#Container> .";
        $turtle .= "\n<> <https://vocab.noeldemartin.com/solid-extra/deepLastModified> \"{$lastModified->toISOString()}\"^^<http://www.w3.org/2001/XMLSchema#dateTime> .";

        $date = now();
        foreach ($this->children($path) as $child) {
            $name = $child['name'];
            $lastModifiedTime = $child['last_modified'];
            $isContainer = str_ends_with($name, '/');

            $turtle .= "\n<> <http://www.w3.org/ns/ldp#contains> <{$path}{$name}> .";
            $turtle .= "\n<{$path}{$name}> a <http://www.w3.org/ns/ldp#Resource> .";

            if ($isContainer) {
                $turtle .= "\n<{$path}{$name}> a <http://www.w3.org/ns/ldp#Container> .";
                $turtle .= "\n<{$path}{$name}> a <http://www.w3.org/ns/ldp#BasicContainer> .";
            } elseif (str_ends_with($name, '.jpg') || str_ends_with($name, '.jpeg')) {
                $turtle .= "\n<{$path}{$name}> a <http://www.w3.org/ns/iana/media-types/image/jpeg#Resource> .";
            } elseif (str_ends_with($name, '.png')) {
                $turtle .= "\n<{$path}{$name}> a <http://www.w3.org/ns/iana/media-types/image/png#Resource> .";
            } elseif (! str_contains($name, '.')) {
                $turtle .= "\n<{$path}{$name}> a <http://www.w3.org/ns/iana/media-types/text/turtle#Resource> .";
            }

            if (! is_null($lastModifiedTime)) {
                $lastModifiedDate = $date->setTimestamp($child['last_modified'])->toISOString();

                $turtle .= "\n<{$path}{$name}> <http://purl.org/dc/terms/modified> \"{$lastModifiedDate}\"^^<http://www.w3.org/2001/XMLSchema#dateTime> .";
                $turtle .= "\n<{$path}{$name}> <http://www.w3.org/ns/posix/stat#modified> {$lastModifiedTime} .";

                if ($isContainer) {
                    $turtle .= "\n<{$path}{$name}> <https://vocab.noeldemartin.com/solid-extra/deepLastModified> \"{$lastModifiedDate}\"^^<http://www.w3.org/2001/XMLSchema#dateTime> .";
                }
            }
        }

        return [
            'content' => $turtle,
            'mime_type' => 'text/turtle',
            'last_modified' => $lastModified,
        ];
    }

    protected function createDocument(string $path, string $content, array $options = []): array
    {
        $overwrite = $options['overwrite'] ?? false;
        $existed = $this->filePathExists($path);

        if (! $overwrite && $existed) {
            abort(409, 'Already exists');
        }

        // TODO ensure directory exists

        $this->cloud()->put($this->prepareFilePath($path), $content);

        return ['status' => $existed ? 200 : 201];
    }

    protected function createContainer(string $path, string $turtle, array $options = []): array
    {
        $overwrite = $options['overwrite'] ?? false;
        $existed = $this->pathExists($path);

        if (! $overwrite && $existed) {
            abort(409, 'Already exists');
        }

        // TODO ensure directory exists

        $this->cloud()->put($this->prepareFilePath("{$path}.meta"), $turtle);

        return ['status' => $existed ? 200 : 201];
    }

    protected function pathExists($path): bool
    {
        try {
            return $this->cloud()->exists($this->preparePath($path));
        } catch (UnableToReadFile) {
            return false;
        }
    }

    protected function filePathExists($path): bool
    {
        try {
            return $this->cloud()->exists($this->prepareFilePath($path));
        } catch (UnableToReadFile) {
            return false;
        }
    }

    protected function children(string $path): array
    {
        $children = [];
        $files = $this->cloud()->getDriver()->listContents($this->preparePath($path));

        foreach ($files as $file) {
            $filename = basename($file->path());

            if (str_starts_with($filename, '.')) {
                continue;
            }

            if ($file->isDir()) {
                $filename .= '/';
            } elseif (str_ends_with($filename, '.ttl')) {
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

    protected function prepareFilePath(string $path): string
    {
        $extension = $this->getExtension($path);

        if (empty($extension) || $extension === 'meta') {
            $path .= '.ttl';
        }

        return $this->preparePath($path);
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
