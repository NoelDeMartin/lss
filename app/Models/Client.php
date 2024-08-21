<?php

namespace App\Models;

use Laravel\Passport\Client as BaseClient;

class Client extends BaseClient
{
    public function skipsAuthorization(): bool
    {
        return app()->runningUnitTests();
    }
}
