<?php

namespace App\Events;

use App\Models\User;

class UserSaved
{
    public function __construct(public User $user) {}
}
