<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
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

describe('codelab-stats:stats', function () {
    beforeEach(fn () => Carbon::setTestNow('2026-09-24 12:00:00'));

    it('shows a report for the last 30 days by default', function () {
        Http::fake([API.'/stats/13*' => mockResponse('stats-country')]);

        $this->artisan('codelab-stats:stats country')
            ->expectsOutputToContain('country for website 13, 2026-08-26 to 2026-09-24')
            ->expectsTable(['Value', 'Count'], [['NL', 8]])
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url()
            === API.'/stats/13?name=country&from=2026-08-26&to=2026-09-24&sort_by=count&sort=desc&per_page=10');
    });

    it('passes the range, search, sorting and website on', function () {
        Http::fake([API.'/stats/14*' => mockResponse('stats-empty')]);

        $this->artisan('codelab-stats:stats', [
            'name' => 'page',
            '--from' => '2026-09-01',
            '--to' => '2026-09-10',
            '--website' => 14,
            '--search' => '/blog',
            '--sort-by' => 'value',
            '--sort' => 'asc',
            '--per-page' => 50,
        ])
            ->expectsOutputToContain('No data for this range')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url() === API.'/stats/14?name=page&from=2026-09-01'
            .'&to=2026-09-10&sort_by=value&sort=asc&per_page=50&search=%2Fblog&search_by=value');
    });

    it('lists the reports when the name is unknown', function () {
        Http::fake();

        $this->artisan('codelab-stats:stats bogus')
            ->expectsOutputToContain('operating_system')
            ->assertFailed();

        Http::assertNothingSent();
    });

    it('rejects an unsupported page size', function () {
        Http::fake();

        $this->artisan('codelab-stats:stats page --per-page=20')
            ->expectsOutputToContain('per page')
            ->assertFailed();

        Http::assertNothingSent();
    });

    it('reports API errors', function () {
        Http::fake([API.'/*' => mockResponse('error-unauthenticated', 401)]);

        $this->artisan('codelab-stats:stats page')
            ->expectsOutputToContain('401')
            ->assertFailed();
    });
});
