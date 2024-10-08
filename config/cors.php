<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'exclude' => [
        'account',
        'account/*',
        'confirm-password',
        'email',
        'email/*',
        'forgot-password',
        'login',
        'logout',
        'password',
        'register',
        'reset-password',
        'reset-password/*',
        'up',
        'verify-email',
        'verify-email/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['WAC-Allow'],

    'max_age' => 0,

    'supports_credentials' => false,

];
