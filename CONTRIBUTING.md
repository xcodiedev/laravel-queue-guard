# Contributing

Thanks for helping improve Laravel Queue Guard.

## Setup

```bash
git clone https://github.com/xcodiedev/laravel-queue-guard
cd laravel-queue-guard
composer install
```

## Checks

All three must pass before a PR is merged (CI enforces them):

```bash
composer test      # PHPUnit
composer analyse   # PHPStan level 6
composer format    # Laravel Pint (run without --test to auto-fix)
```

## Adding a detector

1. Implement `Xcodiedev\QueueGuard\Detectors\Detector` in `src/Detectors/`.
2. It **must not throw** and **must not mutate** the job.
3. A finding **must not include the offending value** — only its property path
   and a description.
4. Register it in `QueueGuardServiceProvider::buildDetectors()` and add a
   `config/queue-guard.php` toggle.
5. Add fixtures to `tests/Fixtures/Jobs.php` and cover it in
   `tests/Unit/JobInspectorTest.php`.

## Pull requests

- One logical change per PR.
- Update `CHANGELOG.md` under `## [Unreleased]`.
- Follow Conventional Commits for the PR title (`feat:`, `fix:`, `docs:` …).
