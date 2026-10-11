<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Viewer Route Path
    |--------------------------------------------------------------------------
    | This is the URI path where the log viewer will be accessible.
    |
    */
    'route-path' => 'logs',

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Route Middleware
    |--------------------------------------------------------------------------
    | These middleware will be assigned to every route in the package.
    | You can add your own middleware here to restrict access.
    |
    */
    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Entries Per Page
    |--------------------------------------------------------------------------
    | How many log entries are rendered per page. Keeping this modest keeps
    | the page fast even for very large log files.
    |
    */
    'per-page' => 50,
];
