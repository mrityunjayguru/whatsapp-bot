<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Scoped to ONLY the public widget endpoints - these must be callable
    | from any third-party company's website that embeds the widget
    | script, so allowed_origins is intentionally wide open, same as the
    | Python bot API's own CORS policy. Every other route in this app
    | (the CRM itself) is untouched by this - 'paths' below is the
    | allowlist, nothing outside it gets CORS headers at all.
    |
    */

    'paths' => ['api/widget/*'],

    'allowed_methods' => ['POST', 'OPTIONS'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
