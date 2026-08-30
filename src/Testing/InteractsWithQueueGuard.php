<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Testing;

use PHPUnit\Framework\Assert;
use Xcodiedev\QueueGuard\GuardReport;
use Xcodiedev\QueueGuard\QueueGuard;

/**
 * Assertions for use in a Laravel application's test suite.
 *
 * Example:
 *
 *   use Xcodiedev\QueueGuard\Testing\InteractsWithQueueGuard;
 *
 *   $this->assertJobPassesQueueGuard(new ProcessOrder($order));
 */
trait InteractsWithQueueGuard
{
    protected function inspectJob(object $job): GuardReport
    {
        return app(QueueGuard::class)->inspect($job);
    }

    protected function assertJobPassesQueueGuard(object $job): GuardReport
    {
        $report = $this->inspectJob($job);

        Assert::assertTrue(
            $report->passes(),
            "Queue Guard reported errors for {$report->job}:\n".$this->format($report),
        );

        return $report;
    }

    protected function assertJobHasNoQueueGuardWarnings(object $job): GuardReport
    {
        $report = $this->inspectJob($job);

        Assert::assertFalse(
            $report->hasWarnings() || $report->hasErrors(),
            "Queue Guard reported findings for {$report->job}:\n".$this->format($report),
        );

        return $report;
    }

    protected function assertJobHasQueueGuardFinding(object $job, string $code): GuardReport
    {
        $report = $this->inspectJob($job);

        $codes = array_map(static fn ($f) => $f->code, $report->findings);

        Assert::assertContains(
            $code,
            $codes,
            "Queue Guard did not report '{$code}' for {$report->job}. Found: ".implode(', ', $codes ?: ['none']),
        );

        return $report;
    }

    private function format(GuardReport $report): string
    {
        return collect($report->findings)
            ->map(static fn ($f) => "  [{$f->severity->value}] "
                .($f->property !== null ? $f->property.': ' : '').$f->message)
            ->implode("\n");
    }
}
