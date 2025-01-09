<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCloudRequest;

class CloudController extends Controller
{
    public function create()
    {
        return view('cloud.create');
    }

    public function store(StoreCloudRequest $request)
    {
        $request->user()->update($request->validated());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
