# Security Policy

## Supported versions

The latest `0.x` release receives security fixes. Once `1.0.0` is released this
policy will be updated to a version window.

## Reporting a vulnerability

Please **do not open a public issue** for security problems.

Report privately through GitHub's
[private vulnerability reporting](https://github.com/xcodiedev/laravel-queue-guard/security/advisories/new)
("Report a vulnerability" on the Security tab).

You can expect an acknowledgement within 72 hours and a fix or mitigation plan
within 14 days for confirmed issues. Please give a reasonable disclosure window
before publishing details.

## Scope notes

This package is a **development-time** tool:

- It is intended to be installed with `--dev` and to run only in `local` and
  `testing` environments.
- It reads job properties by reflection but never mutates them and never makes
  network calls.
- Guard reports intentionally exclude the offending values, but a custom log
  channel or a third-party detector you add could still record sensitive data —
  review your logging setup.
