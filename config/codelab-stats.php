<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Create an API key in your CodeLabs Analytics account. The website ID is
    | the numeric ID of the site to report on — run
    | `php artisan codelab-stats:websites` to list the ones your key can see.
    |
    */

    'key' => env('CODELAB_STATS_KEY'),

    'website_id' => env('CODELAB_STATS_WEBSITE_ID'),

    'base_url' => env('CODELAB_STATS_BASE_URL', 'https://analytics.code-labs.nl/api/v1'),

    'timeout' => (int) env('CODELAB_STATS_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Successful responses are cached. A range that ends today (or later) is
    | still changing, so it gets the short "live" TTL; a range entirely in the
    | past gets the "historical" TTL. Failed requests are never cached.
    |
    */

    'cache' => [
        'enabled' => (bool) env('CODELAB_STATS_CACHE', true),
        'store' => env('CODELAB_STATS_CACHE_STORE'),
        'prefix' => 'codelab-stats',
        'ttl' => [
            'live' => 30,
            'historical' => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON routes
    |--------------------------------------------------------------------------
    |
    | Optional read-only endpoints for a front-end dashboard:
    |   GET /{prefix}/summary?from=Y-m-d&to=Y-m-d
    |   GET /{prefix}/stats?name=page&from=Y-m-d&to=Y-m-d
    |
    | They expose your analytics, so they are off by default and sit behind
    | the given middleware when enabled.
    |
    */

    'routes' => [
        'enabled' => (bool) env('CODELAB_STATS_ROUTES', false),
        'prefix' => 'codelab-stats',
        'middleware' => ['web', 'auth'],
        'name' => 'codelab-stats.',
    ],

];
