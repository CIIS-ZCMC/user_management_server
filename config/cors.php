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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['PUT,DELETE,POST,GET,OPTIONS'],

    'allowed_origins' => [env('CLIENT_DOMAIN'), env("PORTAL_CLIENT_DOMAIN")],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Origin', 'Accept', 'Content-Type', 'x-xsrf-token'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
