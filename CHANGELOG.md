# Changelog

All notable changes to `siberfx/codelab-stats` are documented here.
This project follows [Semantic Versioning](https://semver.org).

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
