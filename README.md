# Laravel Queue Guard

[![Tests](https://github.com/xcodiedev/laravel-queue-guard/actions/workflows/ci.yml/badge.svg)](https://github.com/xcodiedev/laravel-queue-guard/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/xcodiedev/laravel-queue-guard.svg)](https://packagist.org/packages/xcodiedev/laravel-queue-guard)
[![License](https://img.shields.io/packagist/l/xcodiedev/laravel-queue-guard.svg)](LICENSE)

**Catch broken queue payloads before they are dispatched — not after the job fails in production.**

Queue Guard inspects a job the moment it is queued and warns you when the
payload will fail to serialize, get corrupted, leak secrets, or bloat your
queue driver. It runs only in `local` and `testing` by default, so it costs
nothing in production.

```
Queue Guard  ProcessOrder

  ERROR    invoice.pdfHandle — Property holds a resource (stream), which cannot be serialized onto the queue.
  WARNING  customer — App\Models\Customer is carried with 4 eager-loaded relation(s): orders, address, cards, notes.
           ↳ Pass $model->getKey() and call $model->fresh() / load() inside handle().
  WARNING  apiToken — Property name looks sensitive ("token"); its value will sit in the queue payload in plain text.
```

## Why

Laravel serializes every queued job and stores the payload as plain text on
your queue driver (database, Redis, SQS) and in `failed_jobs`. Common, easily
missed mistakes:

| Mistake | What happens |
| --- | --- |
| A closure / stream / PDO handle on a job property | `Serialization of 'Closure' is not allowed` the moment it is pushed |
| Raw image or gzip bytes in a string property | JSON-encoding the payload silently corrupts the data |
| An Eloquent model with eager-loaded relations | the whole graph is written to the payload and restored later — larger and staler than a re-query |
| A 300 KB payload | Amazon SQS rejects anything above 256 KB |
| API tokens / card numbers as job properties | secrets sit in `jobs` and `failed_jobs` in clear text |
| `dispatch()` inside a DB transaction | a worker can run the job before the transaction commits (or after it rolls back) |

Queue Guard detects all of the above.

## Install

```bash
composer require --dev xcodiedev/laravel-queue-guard
```

Requires PHP 8.1+ and Laravel 10, 11 or 12. The service provider is
auto-discovered.

Publish the config if you want to tune it:

```bash
php artisan vendor:publish --tag=queue-guard-config
```

## Usage

### 1. Automatic (recommended)

Once installed, any job dispatched in `local` or `testing` is inspected
automatically. Findings are logged; nothing is thrown.

> The automatic check hooks Laravel's `JobQueueing` event. The `sync` queue
> driver does not fire that event — for `sync`, use the command or the test
> assertions below.

Set `QUEUE_GUARD_MODE=throw` to turn error-level findings into a thrown
`JobFailedGuardException` (useful in CI or when you want a hard stop locally).

### 2. In tests

```php
use Xcodiedev\QueueGuard\Testing\InteractsWithQueueGuard;

class ProcessOrderTest extends TestCase
{
    use InteractsWithQueueGuard;

    public function test_the_job_is_queue_safe(): void
    {
        $order = Order::factory()->create();

        $this->assertJobPassesQueueGuard(new ProcessOrder($order));
        // or:  $this->assertJobHasNoQueueGuardWarnings(new ProcessOrder($order));
        // or:  $this->assertJobHasQueueGuardFinding(new ProcessOrder($order), 'eager_loaded_relations');
    }
}
```

### 3. On demand

```bash
php artisan queue:guard "App\Jobs\ProcessOrder"
php artisan queue:guard "App\Jobs\ProcessOrder" --json
```

(The class is resolved from the container, so it must be constructible without
arguments. For jobs that need constructor data, use the test assertions.)

### 4. Directly

```php
use Xcodiedev\QueueGuard\Facades\QueueGuard;

$report = QueueGuard::inspect(new ProcessOrder($order));

$report->passes();       // bool — no error-level findings
$report->hasWarnings();  // bool
$report->findings;       // list<Xcodiedev\QueueGuard\Finding>
$report->payloadBytes;   // int|null
$report->toArray();      // JSON-ready
```

## Detectors

| Code | Severity | Detects |
| --- | --- | --- |
| `payload_size` | warning / **error** | serialized payload over 64 KB / 256 KB |
| `unserializable_property` | **error** | closures, resources, PDO, generators |
| `eager_loaded_relations` | warning | Eloquent models with loaded relations |
| `eager_loaded_collection` | warning | whole Eloquent collections on a property |
| `binary_string` | warning | non-UTF-8 string properties |
| `sensitive_property` | warning | property names like `password`, `token`, `secret` |
| `sensitive_value` | warning | card-shaped values passing the Luhn check |
| `dispatch_in_transaction` | warning | dispatched inside an open transaction without after-commit |

Each detector can be switched off in `config/queue-guard.php`.

## Security & safety

- **Findings never contain the offending value** — only its property path and a
  description. Reports are safe to log.
- Job property graphs are walked with a **depth limit (6)** and **node limit
  (2000)** so a huge or circular object graph cannot make inspection run away.
- A detector that throws is skipped silently; it can never break a dispatch.
- No network calls, no `eval`, no reflection-based mutation of your jobs.

See [SECURITY.md](SECURITY.md) to report a vulnerability.

## License

MIT — see [LICENSE](LICENSE).
