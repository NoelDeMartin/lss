<?php

namespace App\Http\Controllers;

use App\Support\Facades\Solid;
use App\Support\Facades\Sparql;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorageController extends Controller
{
    public function show()
    {
        $path = request()->getPathInfo();

        if ($path === '/' && ! request()->wantsTurtle()) {
            return view('welcome');
        }

        if ($path !== '/profile/card') {
            $this->authenticate();
        }

        return response(Solid::read($path))
            ->header('WAC-Allow', 'user="read control write"')
            ->header('Content-Type', 'text/turtle');
    }

    public function create()
    {
        $this->authenticate();

        if (request()->header('Content-Type') !== 'text/turtle') {
            abort(400, 'Invalid content type, expected text/turtle');
        }

        $path = request()->getPathInfo();
        $turtle = request()->getContent();

        Solid::create($path, $turtle);

        return response('', 201);
    }

    public function update()
    {
        $this->authenticate();

        if (request()->header('Content-Type') !== 'application/sparql-update') {
            abort(400, 'Invalid content type, expected application/sparql-update');
        }

        $path = request()->getPathInfo();
        $sparql = request()->getContent();

        try {
            Solid::update($path, $sparql);

            return response('', 200);
        } catch (NotFoundHttpException $e) {
            Solid::create($path, Sparql::updateTurtle('', $sparql, ['base' => $path]));

            return response('', 201);
        }
    }

    private function authenticate(): void
    {
        $username = request()->username();

        if (is_null($username)) {
            abort(404);
        }

        $user = Auth::guard('solid')->user();

        if (is_null($user) || $user->username !== $username) {
            abort(401);
        }
    }
}
