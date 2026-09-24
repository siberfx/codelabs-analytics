# Changelog

All notable changes to `siberfx/codelab-stats` are documented here.
This project follows [Semantic Versioning](https://semver.org).

## [1.0.0] - 2026-09-24

### Added

- `CodelabStats` client with `stats()`, `summary()`, `websites()` and `forWebsite()`.
- Caching with a short TTL for ranges that reach today and a longer one for past ranges. Failed requests are not cached.
- Optional JSON routes (`/codelab-stats/summary`, `/codelab-stats/stats`), off by default.
- `codelab-stats:websites` Artisan command to list website IDs.
- Laravel 12 and 13 support.
