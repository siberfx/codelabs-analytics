<?php

use Illuminate\Support\Facades\Http;

it('lists the websites the key can read', function () {
    Http::fake([API.'/websites*' => mockResponse('websites')]);

    $this->artisan('codelab-stats:websites')
        ->expectsTable(['ID', 'Domain', 'Created'], [
            [13, 'example.com', '2026-09-24T18:38:27.000000Z'],
            [14, 'shop.example.com', '2026-09-20T09:12:00.000000Z'],
        ])
        ->assertSuccessful();
});

it('warns when the key can read no websites', function () {
    Http::fake([API.'/websites*' => mockResponse('stats-empty')]);

    $this->artisan('codelab-stats:websites')
        ->expectsOutputToContain('No websites found')
        ->assertSuccessful();
});

it('reports a rejected key', function () {
    Http::fake([API.'/websites*' => mockResponse('error-unauthenticated', 401)]);

    $this->artisan('codelab-stats:websites')
        ->expectsOutputToContain('401')
        ->assertFailed();
});

it('reports a missing key', function () {
    config(['codelab-stats.key' => null]);

    $this->artisan('codelab-stats:websites')
        ->expectsOutputToContain('CODELAB_STATS_KEY')
        ->assertFailed();
});
