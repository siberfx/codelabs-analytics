<?php

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

        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-01&to=2026-09-02&per_page=20&evil=1')
            ->assertOk()
            ->assertExactJson(mockBody('stats-empty'));

        Http::assertSent(fn (Request $request) => (int) $request['per_page'] === 20 && ! isset($request['evil']));
    });

    it('validates the date range', function () {
        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-05&to=2026-09-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    });

    it('passes API errors through with their status', function () {
        Http::fake([API.'/*' => mockResponse('error-unauthenticated', 401)]);

        $this->getJson('/codelab-stats/stats?name=page&from=2026-09-01&to=2026-09-02')
            ->assertUnauthorized()
            ->assertExactJson(mockBody('error-unauthenticated'));
    });
});
