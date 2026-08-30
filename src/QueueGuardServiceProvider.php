<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Xcodiedev\QueueGuard\Console\QueueGuardCommand;
use Xcodiedev\QueueGuard\Detectors\BinaryStringDetector;
use Xcodiedev\QueueGuard\Detectors\Detector;
use Xcodiedev\QueueGuard\Detectors\EagerLoadedRelationDetector;
use Xcodiedev\QueueGuard\Detectors\PayloadSizeDetector;
use Xcodiedev\QueueGuard\Detectors\SensitiveDataDetector;
use Xcodiedev\QueueGuard\Detectors\TransactionSafetyDetector;
use Xcodiedev\QueueGuard\Detectors\UnserializablePropertyDetector;
use Xcodiedev\QueueGuard\Support\PropertyWalker;

final class QueueGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/queue-guard.php', 'queue-guard');

        $this->app->singleton(JobInspector::class, function ($app): JobInspector {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('queue-guard');

            return new JobInspector($this->buildDetectors($config));
        });

        $this->app->singleton(QueueGuard::class, function ($app): QueueGuard {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('queue-guard');

            return new QueueGuard(
                $app->make(JobInspector::class),
                $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
                is_string($config['mode'] ?? null) ? $config['mode'] : 'warn',
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/queue-guard.php' => $this->app->configPath('queue-guard.php'),
            ], 'queue-guard-config');

            $this->commands([QueueGuardCommand::class]);
        }

        $this->registerListener();
    }

    private function registerListener(): void
    {
        /** @var array<string, mixed> $config */
        $config = $this->app['config']->get('queue-guard');

        if (($config['enabled'] ?? true) !== true) {
            return;
        }

        $environments = is_array($config['environments'] ?? null) ? $config['environments'] : [];
        if (! in_array($this->app->environment(), $environments, true)) {
            return;
        }

        if (! class_exists(JobQueueing::class)) {
            return;
        }

        $this->app->make(Dispatcher::class)->listen(
            JobQueueing::class,
            function (JobQueueing $event): void {
                $job = $event->job;

                if (! is_object($job)) {
                    return;
                }

                $ignore = $this->app['config']->get('queue-guard.ignore', []);
                if (is_array($ignore) && in_array($job::class, $ignore, true)) {
                    return;
                }

                if ($this->app['config']->get('queue-guard.enabled', true) !== true) {
                    return;
                }

                $this->app->make(QueueGuard::class)->guard($job, [
                    'transaction_level' => $this->currentTransactionLevel(),
                ]);
            },
        );
    }

    private function currentTransactionLevel(): int
    {
        try {
            return $this->app->bound('db') ? (int) $this->app->make('db')->transactionLevel() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<Detector>
     */
    private function buildDetectors(array $config): array
    {
        $enabled = is_array($config['detectors'] ?? null) ? $config['detectors'] : [];
        $limits = is_array($config['limits'] ?? null) ? $config['limits'] : [];
        $walker = new PropertyWalker(
            (int) ($limits['max_depth'] ?? 6),
            (int) ($limits['max_nodes'] ?? 2000),
        );
        $payload = is_array($config['payload'] ?? null) ? $config['payload'] : [];
        $names = is_array($config['sensitive_names'] ?? null) ? array_values($config['sensitive_names']) : [];

        $all = [
            'payload_size' => fn () => new PayloadSizeDetector(
                (int) ($payload['warn_bytes'] ?? 65536),
                (int) ($payload['error_bytes'] ?? 262144),
            ),
            'unserializable' => fn () => new UnserializablePropertyDetector($walker),
            'eager_loaded_relations' => fn () => new EagerLoadedRelationDetector($walker),
            'binary_string' => fn () => new BinaryStringDetector($walker),
            'sensitive_data' => fn () => new SensitiveDataDetector($walker, $names ?: [
                'password', 'secret', 'token', 'api_key', 'authorization', 'ssn',
            ]),
            'transaction_safety' => fn () => new TransactionSafetyDetector,
        ];

        $detectors = [];
        foreach ($all as $key => $factory) {
            if (($enabled[$key] ?? true) === true) {
                $detectors[] = $factory();
            }
        }

        return $detectors;
    }
}
