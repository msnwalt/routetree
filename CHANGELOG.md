# Reease Notes

## [v1.1.0 (2019-09-23)](https://github.com/laravel/laravel/compare/v1.0.2...v1.1.0)
### Added
- Add config `start_paths_with_locale` to disable locale
- Add parameter to create relative urls to various functions (e.g. `route_node_url()` helper function). Defaults to create absolute urls (previous standard-behaviour).
- Add skip parameter to middleware-config for routes to bypass inherited middleware (thanks to moxx!).
### Changed
- Fall back to `app.locale`, if `app.locales` is not set when determining configures languages.