<?php

namespace Siberfx\CodelabStats;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Siberfx\CodelabStats\Exceptions\MissingConfiguration;

/**
 * Client for the CodeLabs Analytics API (https://analytics.code-labs.nl).
 *
 * Every method returns the decoded JSON body and throws a RequestException
 * when the API answers with a 4xx/5xx, so failures are never cached.
 */
class CodelabStats
{
    /** Metrics fetched by summary(), with the page size requested for each. */
    public const array SUMMARY_METRICS = [
        'pageviews' => [Report::Pageview, 100],
        'visitors' => [Report::Visitor, 100],
        'countries' => [Report::Country, 5],
        'pages' => [Report::Page, 5],
        'devices' => [Report::Device, 10],
        'referrers' => [Report::Referrer, 5],
    ];

    /**
     * @param  array<string, mixed>  $config  the `codelab-stats` config array
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
        private readonly array $config,
        private readonly int|string|null $websiteId = null,
    ) {}

    /** A copy of this client that reports on another website. */
    public function forWebsite(int|string $websiteId): static
    {
        return new static($this->http, $this->cache, $this->config, $websiteId);
    }

    public function websiteId(): string
    {
        $id = $this->websiteId ?? $this->config['website_id'] ?? null;

        if ($id === null || $id === '') {
            throw MissingConfiguration::websiteId();
        }

        return (string) $id;
    }

    /**
     * The websites the API key can read. Not cached — this is a setup call.
     *
     * @param  array<string, mixed>  $params  e.g. search, sort_by, sort, per_page, page
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function websites(array $params = []): array
    {
        return $this->send('websites', $params);
    }

    /**
     * One stats report for the configured website.
     *
     * @param  Report|string  $name  a Report, or its name (browser, page, operating_system, …)
     * @param  array<string, mixed>  $params  search, search_by, sort_by, sort, per_page, page
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function stats(Report|string $name, DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        $name = $name instanceof Report ? $name->value : $name;
        $query = ['name' => $name, 'from' => $this->date($from), 'to' => $this->date($to)] + $params;

        return $this->remember("stats/{$this->websiteId()}", $query, $this->ttlFor($query['to']));
    }

    /**
     * One shortcut per report; each takes the same arguments as stats() after the name.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function browsers(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Browser, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function campaigns(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Campaign, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function cities(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::City, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function continents(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Continent, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function countries(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Country, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function devices(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Device, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function events(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Event, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function languages(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Language, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function operatingSystems(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::OperatingSystem, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function pages(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Page, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function pageviews(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Pageview, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function referrers(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Referrer, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function screenResolutions(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::ScreenResolution, $from, $to, $params);
    }

    /** @return array<string, mixed> */
    public function visitors(DateTimeInterface|string $from, DateTimeInterface|string $to, array $params = []): array
    {
        return $this->stats(Report::Visitor, $from, $to, $params);
    }

    /**
     * Headline numbers for a dashboard: pageview and visitor totals plus the
     * top countries, pages, devices and referrers.
     *
     * @return array{pageviews: int, visitors: int, countries: array, pages: array, devices: array, referrers: array}
     *
     * @throws RequestException
     */
    public function summary(DateTimeInterface|string $from, DateTimeInterface|string $to): array
    {
        $rows = [];

        foreach (self::SUMMARY_METRICS as $key => [$name, $perPage]) {
            $rows[$key] = $this->stats($name, $from, $to, ['per_page' => $perPage])['data'] ?? [];
        }

        return [
            'pageviews' => (int) collect($rows['pageviews'])->sum('count'),
            'visitors' => (int) collect($rows['visitors'])->sum('count'),
            'countries' => $rows['countries'],
            'pages' => $rows['pages'],
            'devices' => $rows['devices'],
            'referrers' => $rows['referrers'],
        ];
    }

    /** A range that reaches today is still changing, so it is cached briefly. */
    private function ttlFor(string $to): int
    {
        $ttl = $this->config['cache']['ttl'] ?? [];

        return $to >= Carbon::today()->toDateString()
            ? (int) ($ttl['live'] ?? 30)
            : (int) ($ttl['historical'] ?? 300);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function remember(string $path, array $query, int $ttl): array
    {
        if (! ($this->config['cache']['enabled'] ?? true) || $ttl <= 0) {
            return $this->send($path, $query);
        }

        // remember() stores nothing when the callback throws, so errors are not cached.
        return $this->cache->remember($this->cacheKey($path, $query), $ttl, fn (): array => $this->send($path, $query));
    }

    /** @param  array<string, mixed>  $query */
    private function cacheKey(string $path, array $query): string
    {
        ksort($query);

        $prefix = $this->config['cache']['prefix'] ?? 'codelab-stats';

        return $prefix.':'.md5($this->baseUrl().'|'.$path.'|'.http_build_query($query));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    private function send(string $path, array $query): array
    {
        $key = $this->config['key'] ?? null;

        if (! is_string($key) || $key === '') {
            throw MissingConfiguration::key();
        }

        return $this->http
            ->baseUrl($this->baseUrl())
            ->withToken($key)
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 10))
            ->get($path, $query)
            ->throw()
            ->json() ?? [];
    }

    private function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? 'https://analytics.code-labs.nl/api/v1'), '/');
    }

    private function date(DateTimeInterface|string $date): string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date;
    }
}
