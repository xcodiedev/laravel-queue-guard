# Changelog

All notable changes to `laravel-queue-guard` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-08-30

### Added

- Initial release.
- `JobInspector` with eight detectors: `payload_size`, `unserializable_property`,
  `eager_loaded_relations`, `eager_loaded_collection`, `binary_string`,
  `sensitive_property`, `sensitive_value`, `dispatch_in_transaction`.
- Automatic inspection of jobs dispatched in `local` / `testing` via the
  `JobQueueing` event, in `warn` (log) or `throw` mode.
- `php artisan queue:guard` command with `--json` output.
- `InteractsWithQueueGuard` test trait with `assertJobPassesQueueGuard`,
  `assertJobHasNoQueueGuardWarnings` and `assertJobHasQueueGuardFinding`.
- `QueueGuard` facade and container binding.
- Publishable `config/queue-guard.php`.
- Depth- and node-limited property graph traversal.

[Unreleased]: https://github.com/xcodiedev/laravel-queue-guard/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/xcodiedev/laravel-queue-guard/releases/tag/v0.1.0
