<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Console;

use Illuminate\Console\Command;
use Throwable;
use Xcodiedev\QueueGuard\GuardReport;
use Xcodiedev\QueueGuard\QueueGuard;

/**
 * Inspect a single job class on demand.
 *
 * The job is resolved from the container, so it must be constructible without
 * arguments (or bound). For jobs that need constructor data, use the
 * InteractsWithQueueGuard assertion inside a test instead.
 */
final class QueueGuardCommand extends Command
{
    protected $signature = 'queue:guard {job : Fully-qualified job class name} {--json : Output JSON}';

    protected $description = 'Inspect a queued job for payload problems before it is dispatched';

    public function handle(QueueGuard $guard): int
    {
        /** @var string $class */
        $class = $this->argument('job');

        if (! class_exists($class)) {
            $this->error("Class {$class} does not exist.");

            return self::FAILURE;
        }

        try {
            $job = $this->laravel->make($class);
        } catch (Throwable $e) {
            $this->error("Could not construct {$class}: {$e->getMessage()}");
            $this->line('Jobs that need constructor arguments should be checked with assertJobPassesQueueGuard() in a test.');

            return self::FAILURE;
        }

        if (! is_object($job)) {
            $this->error('Resolved job is not an object.');

            return self::FAILURE;
        }

        $report = $guard->inspect($job);

        if ($this->option('json')) {
            $this->line((string) json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $report->passes() ? self::SUCCESS : self::FAILURE;
        }

        $this->render($report);

        return $report->passes() ? self::SUCCESS : self::FAILURE;
    }

    private function render(GuardReport $report): void
    {
        $this->line("<info>Job:</info> {$report->job}");
        if ($report->payloadBytes !== null) {
            $this->line('<info>Payload:</info> '.$report->payloadBytes.' bytes');
        }

        if ($report->findings === []) {
            $this->info('No issues found.');

            return;
        }

        $this->newLine();
        foreach ($report->findings as $finding) {
            $tag = $finding->severity->value === 'error' ? 'error' : 'comment';
            $this->line("<{$tag}>".strtoupper($finding->severity->value)."</{$tag}> "
                .($finding->property !== null ? "<info>{$finding->property}</info> — " : '')
                .$finding->message);
            if ($finding->hint !== null) {
                $this->line("      ↳ {$finding->hint}");
            }
        }
    }
}
