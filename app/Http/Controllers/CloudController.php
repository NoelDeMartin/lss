<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloudCreateRequest;
use App\Http\Requests\CloudUpdateRequest;

class CloudController extends Controller
{
    public function create()
    {
        return view('cloud.create');
    }

    public function store(CloudCreateRequest $request)
    {
        $request->user()->update($request->validated());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function update(CloudUpdateRequest $request)
    {
        $user = $request->user();
        $original = $user->getOriginal();

        $user->update($request->validated());

        if ($user->cloudSyncFailed) {
            $user->forgetCloud();
            $user->update($original);

            return redirect()->route('profile.edit')->with('status', 'cloud-sync-failed');
        }

        return redirect()->route('profile.edit')->with('status', 'cloud-updated');
    }
}
