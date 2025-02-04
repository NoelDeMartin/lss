<?php

namespace App\Providers;

use AaronFrancis\Solo\Commands\EnhancedTailCommand;
use AaronFrancis\Solo\Facades\Solo;
use Illuminate\Support\ServiceProvider;

class SoloServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Solo may not (should not!) exist in prod, so we have to
        // check here first to see if it's installed.
        if (! class_exists('\AaronFrancis\Solo\Manager')) {
            return;
        }

        Solo::useTheme('dark')
            ->addCommands([
                'HTTP' => 'php artisan serve --host=localhost',
                EnhancedTailCommand::make('Logs', 'tail -f -n 100 ' . storage_path('logs/laravel.log')),
                'About' => 'php artisan solo:about',
            ]);
    }
}
