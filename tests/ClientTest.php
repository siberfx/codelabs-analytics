<?php

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Siberfx\CodelabStats\CodelabStats as Client;
use Siberfx\CodelabStats\Exceptions\MissingConfiguration;
use Siberfx\CodelabStats\Facades\CodelabStats;

beforeEach(fn () => Carbon::setTestNow('2026-09-24 12:00:00'));

it('requests a stats report with the bearer key and returns the body', function () {
    Http::fake([API.'/stats/13*' => mockResponse('stats-country')]);

    $body = CodelabStats::stats('country', '2026-09-01', Carbon::parse('2026-09-10'), ['per_page' => 5]);

    expect($body)->toBe(mockBody('stats-country'));

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request->hasHeader('Accept', 'application/json')
        && $request->url() === API.'/stats/13?name=country&from=2026-09-01&to=2026-09-10&per_page=5');
});

it('caches successful responses', function () {
    Http::fake([API.'/*' => mockResponse('stats-empty')]);

    CodelabStats::stats('page', '2026-09-01', '2026-09-10');
    CodelabStats::stats('page', '2026-09-01', '2026-09-10');

    Http::assertSentCount(1);
});

it('keeps a separate cache per website', function () {
    Http::fake([API.'/*' => mockResponse('stats-empty')]);

    CodelabStats::stats('page', '2026-09-01', '2026-09-10');
    CodelabStats::forWebsite(14)->stats('page', '2026-09-01', '2026-09-10');

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), API.'/stats/14?'));
});

it('caches a range ending today only briefly', function () {
    Http::fake([API.'/*' => mockResponse('stats-empty')]);

    CodelabStats::stats('page', '2026-09-01', '2026-09-24');
    CodelabStats::stats('page', '2026-09-01', '2026-09-10');

    $this->travel(31)->seconds();

    CodelabStats::stats('page', '2026-09-01', '2026-09-24'); // live: expired
    CodelabStats::stats('page', '2026-09-01', '2026-09-10'); // historical: still cached

    Http::assertSentCount(3);
});

it('throws on API errors and does not cache them', function () {
    Http::fake([API.'/*' => Http::sequence()
        ->push(mockBody('error-validation'), 422)
        ->push(mockBody('stats-empty'))]);

    expect(fn () => CodelabStats::stats('bogus', '2026-09-01', '2026-09-10'))
        ->toThrow(RequestException::class);

    expect(CodelabStats::stats('bogus', '2026-09-01', '2026-09-10'))->toBe(mockBody('stats-empty'));
});

it('skips the cache when disabled', function () {
    config(['codelab-stats.cache.enabled' => false]);
    Http::fake([API.'/*' => mockResponse('stats-empty')]);

    CodelabStats::stats('page', '2026-09-01', '2026-09-10');
    CodelabStats::stats('page', '2026-09-01', '2026-09-10');

    Http::assertSentCount(2);
});

it('builds a summary from six reports', function () {
    Http::fake(fn (Request $request) => mockResponse(match ($request['name']) {
        'pageview', 'visitor', 'country' => 'stats-'.$request['name'],
        default => 'stats-empty',
    }));

    $summary = CodelabStats::summary('2026-09-01', '2026-09-02');

    expect($summary)->toBe([
        'pageviews' => 15,
        'visitors' => 3,
        'countries' => mockBody('stats-country')['data'],
        'pages' => [],
        'devices' => [],
        'referrers' => [],
    ]);

    Http::assertSentCount(6);
    Http::assertSent(fn (Request $request) => $request['name'] === 'device' && (int) $request['per_page'] === 10);
});

it('lists websites without caching', function () {
    Http::fake([API.'/websites*' => mockResponse('websites')]);

    expect(CodelabStats::websites()['data'][0]['id'])->toBe(13);
    CodelabStats::websites();

    Http::assertSentCount(2);
});

it('honours a custom base url', function () {
    config(['codelab-stats.base_url' => 'https://stats.example.test/api/v1/']);
    Http::fake(['https://stats.example.test/api/v1/websites' => mockResponse('websites')]);

    expect(CodelabStats::websites())->toBe(mockBody('websites'));
});

it('fails clearly without an API key', function () {
    config(['codelab-stats.key' => null]);

    CodelabStats::websites();
})->throws(MissingConfiguration::class, 'CODELAB_STATS_KEY');

it('fails clearly without a website id', function () {
    config(['codelab-stats.website_id' => null]);

    CodelabStats::stats('page', '2026-09-01', '2026-09-10');
})->throws(MissingConfiguration::class, 'CODELAB_STATS_WEBSITE_ID');

it('resolves the client from the container', function () {
    expect(app(Client::class))->toBeInstanceOf(Client::class)
        ->and(app(Client::class)->websiteId())->toBe('13');
});
