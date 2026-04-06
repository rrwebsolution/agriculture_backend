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

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    // During mobile debugging it's helpful to allow the device origin.
    // For quick testing we allow all origins and disable credentialed requests.
    // IMPORTANT: revert to stricter origins and enable credentials when done.
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // 🌟 GI-UPDATE: 86400 seconds (24 hours). 
    // Gitudloan ang browser nga i-cache ang preflight (OPTIONS) 
    // aron dili na mag sige'g padala og duplicate request kada click.
    'max_age' => 86400,

    'supports_credentials' => false,

];