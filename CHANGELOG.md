# Changelog

All notable changes to `siberfx/codelab-stats` are documented here.
This project follows [Semantic Versioning](https://semver.org).

## [1.2.0] - 2026-09-24

### Added

- `Report` enum with all 14 reports: `browser`, `campaign`, `city`, `continent`, `country`, `device`, `event`, `language`, `operating_system`, `page`, `pageview`, `referrer`, `screen_resolution` and `visitor`. It also includes the allowed option values (`SEARCH_BY`, `SORT_BY`, `SORT`, `PER_PAGE`).
- `stats()` accepts a `Report` case as well as a string.
- One shortcut per report: `browsers()`, `campaigns()`, `cities()`, `continents()`, `countries()`, `devices()`, `events()`, `languages()`, `operatingSystems()`, `pages()`, `pageviews()`, `referrers()`, `screenResolutions()`, `visitors()`.
- `codelab-stats:stats {name}` Artisan command with `--from`, `--to`, `--website`, `--search`, `--sort-by`, `--sort` and `--per-page`.

### Changed

- The stats route validates `name`, `search_by`, `sort_by`, `sort`, `per_page` and `page` against the values the API accepts. Invalid input now gets a local **422** instead of a **502** carrying CodeLabs' validation error. This also rejects page sizes other than 10, 25, 50 and 100.
- `illuminate/validation` is now a declared dependency. The routes already used it.

## [1.1.0] - 2026-09-24

### Changed

- The JSON routes answer CodeLabs failures with **502** and a readable `message` (plus `upstream_status` and `upstream`), instead of passing the API's status through. A CodeLabs 401 used to reach the browser as a 401, which front ends read as an expired session, and a 404 as a missing route. If you checked for the passed-through status, check `upstream_status` instead.
- A missing key or website ID on the routes answers **503** with the configuration hint, instead of a 500.
- An unreachable API answers **502** instead of a 500.

## [1.0.0] - 2026-09-24

### Added

- `CodelabStats` client with `stats()`, `summary()`, `websites()` and `forWebsite()`.
- Caching with a short TTL for ranges that reach today and a longer one for past ranges. Failed requests are not cached.
- Optional JSON routes (`/codelab-stats/summary`, `/codelab-stats/stats`), off by default.
- `codelab-stats:websites` Artisan command to list website IDs.
- Laravel 12 and 13 support.
