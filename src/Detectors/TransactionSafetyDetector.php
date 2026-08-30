<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Detectors;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Xcodiedev\QueueGuard\Finding;
use Xcodiedev\QueueGuard\Severity;

/**
 * Flags a job dispatched while a database transaction is open when the job is
 * not after-commit aware. A worker can pick the job up before (or instead of, on
 * rollback) the transaction commits, so it sees stale or missing rows.
 *
 * Only meaningful when inspection runs during a real dispatch; the transaction
 * level is supplied through $context.
 */
final class TransactionSafetyDetector implements Detector
{
    public function inspect(object $job, array $context): array
    {
        $level = $context['transaction_level'] ?? 0;

        if (! is_int($level) || $level < 1) {
            return [];
        }

        if ($this->isAfterCommitAware($job)) {
            return [];
        }

        return [new Finding(
            Severity::Warning,
            'dispatch_in_transaction',
            sprintf('Job dispatched inside an open database transaction (level %d) without after-commit handling.', $level),
            null,
            'Add ->afterCommit() to the dispatch, implement ShouldQueueAfterCommit, or set after_commit=true on the connection.',
        )];
    }

    private function isAfterCommitAware(object $job): bool
    {
        if (interface_exists(ShouldQueueAfterCommit::class) && $job instanceof ShouldQueueAfterCommit) {
            return true;
        }

        // Queueable trait exposes a nullable $afterCommit flag.
        if (property_exists($job, 'afterCommit') && ($job->afterCommit ?? null) === true) {
            return true;
        }

        return false;
    }
}
