<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard;

use Psr\Log\LoggerInterface;
use Xcodiedev\QueueGuard\Exceptions\JobFailedGuardException;

/**
 * Entry point for the package. Resolve it from the container or use the
 * QueueGuard facade.
 */
final class QueueGuard
{
    public function __construct(
        private readonly JobInspector $inspector,
        private readonly ?LoggerInterface $logger = null,
        private readonly string $mode = 'warn',
    ) {}

    /**
     * Inspect a job and return the report. Never throws, never mutates.
     *
     * @param  array<string, mixed>  $context
     */
    public function inspect(object $job, array $context = []): GuardReport
    {
        return $this->inspector->inspect($job, $context);
    }

    /**
     * Inspect a job and act on the result according to the configured mode:
     * log warnings, and (in "throw" mode) throw on any error-level finding.
     *
     * @param  array<string, mixed>  $context
     */
    public function guard(object $job, array $context = []): GuardReport
    {
        $report = $this->inspect($job, $context);

        if ($report->findings === []) {
            return $report;
        }

        $this->log($report);

        if ($this->mode === 'throw' && $report->hasErrors()) {
            throw new JobFailedGuardException($report);
        }

        return $report;
    }

    private function log(GuardReport $report): void
    {
        if ($this->logger === null) {
            return;
        }

        foreach ($report->findings as $finding) {
            $message = sprintf(
                'queue-guard: %s%s',
                $finding->property !== null ? $finding->property.' — ' : '',
                $finding->message,
            );

            $context = ['job' => $report->job, 'code' => $finding->code];

            $finding->severity === Severity::Error
                ? $this->logger->error($message, $context)
                : $this->logger->warning($message, $context);
        }
    }
}
