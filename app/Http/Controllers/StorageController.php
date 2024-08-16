<?php

namespace App\Http\Controllers;

use App\Support\Facades\PodStorage;
use EasyRdf\Graph;
use EasyRdf\Parser\Turtle as TurtleParser;
use EasyRdf\Serialiser\Turtle as TurtleSerializer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorageController extends Controller
{
    public function read()
    {
        $path = request()->getPathInfo();

        return response($this->readTurtle($path))->header('Content-Type', 'text/turtle');
    }

    public function create()
    {
        if (request()->header('Content-Type') !== 'text/turtle') {
            abort(400, 'Invalid content type, expected text/turtle');
        }

        $path = request()->getPathInfo();
        $turtle = request()->getContent();

        $this->createTurtle($path, $turtle);

        return response('', 201);
    }

    public function update()
    {
        if (request()->header('Content-Type') !== 'application/sparql-update') {
            dd(request()->header('Content-Type'));

            abort(400, 'Invalid content type, expected application/sparql-update');
        }

        $path = request()->getPathInfo();
        $sparql = request()->getContent();
        $status = $this->updateTurtle($path, $sparql);

        return response('', $status);
    }

    private function readTurtle(string $path): string
    {
        $turtle = str_ends_with($path, '/') ? $this->readContainerTurtle($path) : PodStorage::read("$path.ttl");

        if (is_null($turtle)) {
            return abort(404);
        }

        return $turtle;
    }

    private function createTurtle(string $path, string $turtle): void
    {
        if (str_ends_with($path, '/')) {
            PodStorage::write("$path.meta.ttl", $turtle);

            return;
        }

        PodStorage::write("$path.ttl", $turtle);
    }

    private function updateTurtle(string $path, string $sparql): int
    {
        try {
            $status = 200;
            $turtle = $this->readTurtle($path);
        } catch (NotFoundHttpException $e) {
            $status = 201;
            $turtle = '';

            $this->createTurtle($path, $turtle);
        }

        $baseUri = url($path);
        $documentGraph = new Graph;
        $insertGraph = new Graph;
        $parser = new TurtleParser;
        $serializer = new TurtleSerializer;

        preg_match('/INSERT DATA {(?<insert>.*)}/s', $sparql, $matches);

        $parser->parse($documentGraph, $turtle, 'turtle', $baseUri);
        $parser->parse($insertGraph, $matches['insert'], 'turtle', $baseUri);

        foreach ($insertGraph->resources() as $resource) {
            foreach ($resource->propertyUris() as $property) {
                $literals = $resource->allLiterals("<$property>");
                $resources = $resource->allResources("<$property>");

                foreach ($literals as $value) {
                    $documentGraph->addLiteral($resource, $property, $value);
                }

                foreach ($resources as $value) {
                    $documentGraph->addResource($resource, $property, $value);
                }
            }
        }

        PodStorage::write("$path.ttl", $serializer->serialise($documentGraph, 'turtle'));

        return $status;
    }

    private function readContainerTurtle(string $path): ?string
    {
        $turtle = PodStorage::read("$path.meta.ttl");

        if (is_null($turtle)) {
            return null;
        }

        $turtle .= '<> a <http://www.w3.org/ns/ldp#Container> .';

        foreach (PodStorage::list($path) as $childPath) {
            $turtle .= "\n<> <http://www.w3.org/ns/ldp#contains> <$path$childPath> .";
        }

        return $turtle;
    }
}
