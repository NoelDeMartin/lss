<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Facades\Solid;
use App\Support\Facades\Sparql;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorageController extends Controller
{
    public function create(): Response
    {
        $this->authenticate();

        $contentType = request()->header('Content-Type');
        $path = request()->getPathInfo();

        if ($contentType !== 'text/turtle' && str_ends_with($path, '/')) {
            abort(400, 'Invalid content type, expected text/turtle');
        }

        $content = is_string($contentType) && str_starts_with($contentType, 'image/')
            ? (string) file_get_contents('php://input')
            : request()->getContent();
        /** @var array{status: int} $result */
        $result = Solid::create($path, $content, ['overwrite' => true]);

        return response('', $result['status']);
    }

    public function show(): Response|RedirectResponse|View
    {
        $path = request()->getPathInfo();

        if ($path === '/' && ! request()->wantsTurtle()) {
            if (Auth::check()) {
                return redirect()->route('dashboard');
            }

            return view('welcome');
        }

        if (! preg_match('/^\/profile\/(card|avatar\.(pn|jpe?)g)$/', $path)) {
            $this->authenticate();
        }

        $file = Solid::read($path);

        return response($file['content'])
            ->header('WAC-Allow', 'user="read control write"')
            ->header('Content-Type', $file['mime_type'])
            ->header('Last-Modified', $file['last_modified']->toRfc7231String());
    }

    public function update(): Response
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

        /** @var User|null $user */
        $user = Auth::guard('solid')->user();

        if (is_null($user) || $user->username !== $username) {
            abort(401);
        }

        return $user;
    }
}
