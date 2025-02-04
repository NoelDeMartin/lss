<?php

namespace App\Listeners;

use App\Events\UserSaved;
use App\Support\Facades\Solid;
use Throwable;

class SyncSolidProfile
{
    public function handle(UserSaved $event): void
    {
        if (! $event->user->hasCloud()) {
            return;
        }

        try {
            Solid::syncProfile($event->user);
        } catch (Throwable) {
            $event->user->cloudSyncFailed = true;
        }
    }
}
