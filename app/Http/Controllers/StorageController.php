<?php

namespace App\Http\Controllers;

use App\Support\Facades\PodStorage;
use App\Support\Facades\Sparql;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorageController extends Controller
{
    public function read()
    {
        $path = request()->getPathInfo();

        return response($this->readTurtle($path))
            ->header('WAC-Allow', 'user="read control write"')
            ->header('Content-Type', 'text/turtle');
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

        PodStorage::write("$path.ttl", Sparql::updateTurtle($turtle, $sparql, ['base' => $path]));

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
