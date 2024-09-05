<?php

namespace App\Listeners;

use App\Events\UserSaved;
use App\Support\Facades\Solid;

class SyncSolidProfile
{
    public function handle(UserSaved $event): void
    {
        if (! $event->user->hasCloud()) {
            return;
        }

        Solid::syncProfile($event->user);
    }
}
