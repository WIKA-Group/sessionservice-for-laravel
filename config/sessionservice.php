<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Session Service
    |--------------------------------------------------------------------------
    |
    | Connection details for Session Service.
    |
    */

    'connection' => [
        'tenant_id' => env('SESSION_SERVICE_TENANT_ID'),
        'client_id' => env('SESSION_SERVICE_CLIENT_ID'),
        'secret' => env('SESSION_SERVICE_CLIENT_SECRET'),

        'base_url' => env('SESSION_SERVICE_BASE_URL'),
        'redirect_uri' => env('SESSION_SERVICE_REDIRECT_URL'),
        'scope' => env('SESSION_SERVICE_SCOPE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Paths are relative to the /sessionservice prefix.
    | The callback route must match the configured redirect_uri.
    |
    */

    'routes' => [
        'callback' => '/callback',
        'logout' => '/logout',
        'home' => '/',
    ],

];
