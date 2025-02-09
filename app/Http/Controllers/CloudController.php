<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloudCreateRequest;
use App\Http\Requests\CloudUpdateRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

class CloudController extends Controller
{
    public function create()
    {
        return view('cloud.create');
    }

    public function store(CloudCreateRequest $request)
    {
        return $this->updateCloud($request) ?? redirect()->intended(route('dashboard', absolute: false));
    }

    public function update(CloudUpdateRequest $request)
    {
        return $this->updateCloud($request) ?? redirect()->back()->with('status', 'cloud-updated');
    }

    private function updateCloud(FormRequest $request): ?RedirectResponse
    {
        $user = $request->user();
        $original = $user->getOriginal();

        $user->update($request->validated());

        if ($user->cloudSyncFailed) {
            $user->forgetCloud();
            $user->update($original);
            $request->flash();

            return redirect()->back()->with('status', 'cloud-sync-failed');
        }

        return null;
    }
}
