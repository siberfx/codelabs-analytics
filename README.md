# CodeLabs Analytics for Laravel

[![Tests](https://github.com/siberfx/codelabs-stats/actions/workflows/tests.yml/badge.svg)](https://github.com/siberfx/codelabs-stats/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/siberfx/codelabs-stats.svg)](https://packagist.org/packages/siberfx/codelabs-stats)
[![License](https://img.shields.io/packagist/l/siberfx/codelabs-stats.svg)](LICENSE)

A small Laravel client for [CodeLabs Analytics](https://analytics.code-labs.nl). It reads your site's
statistics with caching, builds a one-call dashboard summary, and can optionally expose read-only
JSON routes for a front-end dashboard.

- Pageviews, visitors, countries, pages, devices, referrers and every other report the API offers
- Smart caching: ranges that end today refresh every 30 seconds; past ranges are cached for 5 minutes
- Failed requests throw and are never cached
- `php artisan codelab-stats:websites` to find your website ID, `php artisan codelab-stats:stats` to check any report
- Laravel 12 and 13, PHP 8.3+

## Installation

```bash
composer require siberfx/codelab-stats
```

Add your credentials to `.env`:

```dotenv
CODELAB_STATS_KEY=your-api-key
CODELAB_STATS_WEBSITE_ID=13
```

Don't know your website ID? List the sites your key can read:

```bash
php artisan codelab-stats:websites
```

```
+----+----------------+-----------------------------+
| ID | Domain         | Created                     |
+----+----------------+-----------------------------+
| 13 | example.com    | 2026-09-24T18:38:27.000000Z |
+----+----------------+-----------------------------+
```

To change the defaults, publish the config file:

```bash
php artisan vendor:publish --tag=codelab-stats-config
```

## Usage

### Dashboard summary

```php
use Siberfx\CodelabStats\Facades\CodelabStats;

$summary = CodelabStats::summary('2026-09-01', now());

// [
//     'pageviews' => 1520,
//     'visitors'  => 610,
//     'countries' => [['value' => 'NL', 'count' => 320], ...], // top 5
//     'pages'     => [...],                                    // top 5
//     'devices'   => [...],                                    // top 10
//     'referrers' => [...],                                    // top 5
// ]
```

Dates can be `Y-m-d` strings or any `DateTimeInterface` (Carbon included).

`pageviews` and `visitors` are the sums of the daily rows; `summary()` fetches up to 100 of them,
so keep summary ranges to 100 days or less.

### A single report

```php
$pages = CodelabStats::stats('page', '2026-09-01', '2026-09-30', [
    'per_page' => 20,
    'sort_by'  => 'count',
    'sort'     => 'desc',
]);

$pages['data']; // [['value' => '/pricing', 'count' => 84], ...]
```

The response body is returned as-is. The name can be a string or a `Report` case, and every report
has a shortcut that takes the same arguments:

| `Report` case      | Name                | Shortcut              |
| ------------------ | ------------------- | --------------------- |
| `Browser`          | `browser`           | `browsers()`          |
| `Campaign`         | `campaign`          | `campaigns()`         |
| `City`             | `city`              | `cities()`            |
| `Continent`        | `continent`         | `continents()`        |
| `Country`          | `country`           | `countries()`         |
| `Device`           | `device`            | `devices()`           |
| `Event`            | `event`             | `events()`            |
| `Language`         | `language`          | `languages()`         |
| `OperatingSystem`  | `operating_system`  | `operatingSystems()`  |
| `Page`             | `page`              | `pages()`             |
| `Pageview`         | `pageview`          | `pageviews()`         |
| `Referrer`         | `referrer`          | `referrers()`         |
| `ScreenResolution` | `screen_resolution` | `screenResolutions()` |
| `Visitor`          | `visitor`           | `visitors()`          |

```php
use Siberfx\CodelabStats\Report;

CodelabStats::stats(Report::OperatingSystem, '2026-09-01', '2026-09-30');
CodelabStats::screenResolutions('2026-09-01', '2026-09-30', ['per_page' => 25]);
CodelabStats::pages('2026-09-01', '2026-09-30', ['search' => '/blog', 'search_by' => 'value']);
```

Options accepted by the API:

| Option      | Values                     | Default |
| ----------- | -------------------------- | ------- |
| `search`    | any text                   | —       |
| `search_by` | `value`                    | —       |
| `sort_by`   | `count`, `value`           | `count` |
| `sort`      | `desc`, `asc`              | `desc`  |
| `per_page`  | `10`, `25`, `50`, `100`    | `10`    |
| `page`      | page number                | `1`     |

These values are also available as `Report::SEARCH_BY`, `Report::SORT_BY`, `Report::SORT` and
`Report::PER_PAGE`.

### From the command line

Check any report for a site without writing code:

```bash
php artisan codelab-stats:stats country                       # last 30 days, top 10
php artisan codelab-stats:stats page --from=2026-09-01 --to=2026-09-30 --per-page=25
php artisan codelab-stats:stats referrer --website=14 --search=google
php artisan codelab-stats:stats pageview --sort-by=value --sort=asc --per-page=100
```

```
+-------+-------+
| Value | Count |
+-------+-------+
| NL    | 320   |
| DE    | 112   |
+-------+-------+
```

### Several websites

```php
CodelabStats::forWebsite(14)->summary('2026-09-01', '2026-09-30');
```

### Dependency injection

```php
use Siberfx\CodelabStats\CodelabStats;

public function __invoke(CodelabStats $stats)
{
    return $stats->summary(now()->subDays(29), now());
}
```

### Errors

When the API answers with a 4xx or 5xx, the client throws `Illuminate\Http\Client\RequestException`,
and nothing is cached. A missing key or website ID throws
`Siberfx\CodelabStats\Exceptions\MissingConfiguration`.

## JSON routes (optional)

For a JavaScript dashboard, turn on the built-in endpoints:

```dotenv
CODELAB_STATS_ROUTES=true
```

| Method | URI                                                     | Returns             |
| ------ | ------------------------------------------------------- | ------------------- |
| GET    | `/codelab-stats/summary?from=2026-09-01&to=2026-09-30`   | the summary above   |
| GET    | `/codelab-stats/stats?name=page&from=…&to=…&per_page=20` | one raw report      |

The stats route forwards only `search`, `search_by`, `sort_by`, `sort`, `per_page` and `page`
besides `name`, `from` and `to`. It checks them against the values in the table above. An unknown report or
an unsupported value gets a normal Laravel **422** and never reaches CodeLabs.

When CodeLabs fails, the routes answer **502** with a readable message and the API's own status and
body, so your front end can show what went wrong:

```json
{
    "message": "CodeLabs Analytics has no website 12 for this API key.",
    "upstream_status": 404,
    "upstream": { "message": "Resource not found.", "status": 404 }
}
```

A missing key or website ID answers **503**. The API's own status is never passed through as the
response status: a CodeLabs 401 would otherwise look like an expired session to your front end.

**These routes expose your analytics.** They use the `web` and `auth` middleware by default; tighten
that in `config/codelab-stats.php` (for example `['web', 'auth', 'can:view-analytics']`). You can
change the prefix and route names there too.

## Configuration

| Key                      | Env                          | Default                                 |
| ------------------------ | ---------------------------- | --------------------------------------- |
| `key`                    | `CODELAB_STATS_KEY`          | —                                       |
| `website_id`             | `CODELAB_STATS_WEBSITE_ID`   | —                                       |
| `base_url`               | `CODELAB_STATS_BASE_URL`     | `https://analytics.code-labs.nl/api/v1` |
| `timeout`                | `CODELAB_STATS_TIMEOUT`      | `10` seconds                            |
| `cache.enabled`          | `CODELAB_STATS_CACHE`        | `true`                                  |
| `cache.store`            | `CODELAB_STATS_CACHE_STORE`  | your default cache store                |
| `cache.ttl.live`         | —                            | `30` seconds                            |
| `cache.ttl.historical`   | —                            | `300` seconds                           |
| `routes.enabled`         | `CODELAB_STATS_ROUTES`       | `false`                                 |
| `routes.middleware`      | —                            | `['web', 'auth']`                       |

## Testing

```bash
composer test
```

In your own tests, fake the HTTP calls as usual:

```php
Http::fake(['analytics.code-labs.nl/*' => Http::response(['data' => []])]);
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT. See [LICENSE](LICENSE).
