<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

it('registers no routes by default', function () {
    expect(Route::has('codelab-stats.summary'))->toBeFalse()
        ->and(Route::has('codelab-stats.stats'))->toBeFalse();
});

describe('when enabled', function () {
    beforeEach(function () {
        $this->app['config']->set('codelab-stats.routes.enabled', true);
        $this->app['config']->set('codelab-stats.routes.middleware', []);
        $this->app->getProvider(\Siberfx\CodelabStats\CodelabStatsServiceProvider::class)->boot();
        $this->app['router']->getRoutes()->refreshNameLookups();
    });

    it('serves the summary', function () {
        Http::fake([API.'/*' => mockResponse('stats-pageview')]);

        $this->getJson('/codelab-stats/summary?from=2026-09-01&to=2026-09-02')
            ->assertOk()
            ->assertJsonPath('pageviews', 15)
            ->assertJsonPath('visitors', 15);
    });

    it('forwards only known stats parameters', function () {
        Http::fake([API.'/*' => mockResponse('stats-empty')]);

        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-01&to=2026-09-02&per_page=25&evil=1')
            ->assertOk()
            ->assertExactJson(mockBody('stats-empty'));

        Http::assertSent(fn (Request $request) => (int) $request['per_page'] === 25 && ! isset($request['evil']));
    });

    it('validates the date range', function () {
        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-05&to=2026-09-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    });

    it('rejects unknown reports and option values before calling CodeLabs', function (string $query, string $field) {
        Http::fake();

        $this->getJson("/codelab-stats/stats?from=2026-09-01&to=2026-09-02&{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        Http::assertNothingSent();
    })->with([
        ['name=bogus', 'name'],
        ['name=page&search_by=domain', 'search_by'],
        ['name=page&sort_by=date', 'sort_by'],
        ['name=page&sort=up', 'sort'],
        ['name=page&per_page=20', 'per_page'],
        ['name=page&page=0', 'page'],
    ]);

    it('forwards every documented option', function () {
        Http::fake([API.'/*' => mockResponse('stats-empty')]);

        $this->getJson('/codelab-stats/stats?name=screen_resolution&from=2026-09-01&to=2026-09-02'
            .'&search=1920&search_by=value&sort_by=value&sort=asc&per_page=100&page=2')
            ->assertOk();

        Http::assertSent(fn (Request $request) => $request->url() === API.'/stats/13?name=screen_resolution'
            .'&from=2026-09-01&to=2026-09-02&search=1920&search_by=value&sort_by=value&sort=asc&per_page=100&page=2');
    });

    it('answers a rejected key with 502, not 401', function () {
        Http::fake([API.'/*' => mockResponse('error-unauthenticated', 401)]);

        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-01&to=2026-09-02')
            ->assertStatus(502)
            ->assertJsonPath('message', 'CodeLabs Analytics rejected the API key.')
            ->assertJsonPath('upstream_status', 401)
            ->assertJsonPath('upstream', mockBody('error-unauthenticated'));
    });

    it('names the website when CodeLabs does not know it', function () {
        Http::fake([API.'/*' => Http::response(['message' => 'Resource not found.', 'status' => 404], 404)]);

        $this->getJson('/codelab-stats/summary?from=2026-09-01&to=2026-09-02')
            ->assertStatus(502)
            ->assertJsonPath('message', 'CodeLabs Analytics has no website 13 for this API key.')
            ->assertJsonPath('upstream_status', 404);
    });

    it('surfaces the first validation message from CodeLabs', function () {
        Http::fake([API.'/*' => mockResponse('error-validation', 422)]);

        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-01&to=2026-09-02')
            ->assertStatus(502)
            ->assertJsonPath('message', 'CodeLabs Analytics rejected the request: The selected name is invalid.');
    });

    it('answers 502 when CodeLabs cannot be reached', function () {
        Http::fake([API.'/*' => fn () => throw new ConnectionException('timeout')]);

        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-01&to=2026-09-02')
            ->assertStatus(502)
            ->assertJsonPath('message', 'CodeLabs Analytics could not be reached.');
    });

    it('answers 503 when the package is not configured', function () {
        config(['codelab-stats.key' => null]);

        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-01&to=2026-09-02')
            ->assertStatus(503)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'CODELAB_STATS_KEY'));
    });
});
