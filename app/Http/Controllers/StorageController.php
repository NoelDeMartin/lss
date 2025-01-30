<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            if (Auth::check()) {
                return redirect()->route('dashboard');
            }

            return view('welcome');
        }

        if ($path !== '/profile/card') {
            $this->authenticate();
        }

        return response(Solid::read($path))
            ->header('WAC-Allow', 'user="read control write"')
            ->header('Content-Type', Solid::mimeType($path));
    }

    public function create()
    {
        $this->authenticate();

        $path = request()->getPathInfo();
        $content = request()->getContent();

        if (request()->header('Content-Type') !== 'text/turtle' && str_ends_with($path, '/')) {
            abort(400, 'Invalid content type, expected text/turtle');
        }

        Solid::create($path, $content);

        return response('', 201);
    }

    public function update()
    {
        $user = $this->authenticate();

        if (request()->header('Content-Type') !== 'application/sparql-update') {
            abort(400, 'Invalid content type, expected application/sparql-update');
        }

        $path = request()->getPathInfo();
        $sparql = request()->getContent();

        try {
            Solid::update($path, $sparql);

            return response('', 200);
        } catch (NotFoundHttpException $e) {
            Solid::create($path, Sparql::updateTurtle('', $sparql, [
                'base' => $user->url(),
                'document' => $user->url($path),
            ]));

            return response('', 201);
        }
    }

    private function authenticate(): User
    {
        $username = request()->username();

        if (is_null($username)) {
            abort(404);
        }

        $user = Auth::guard('solid')->user();

        if (is_null($user) || $user->username !== $username) {
            abort(401);
        }

        return $user;
    }
}
