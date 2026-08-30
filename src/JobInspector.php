<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard;

use Throwable;
use Xcodiedev\QueueGuard\Detectors\Detector;

/**
 * Runs every configured detector against a job and collects a GuardReport.
 *
 * The inspector is framework-agnostic: it takes a job object and an optional
 * context array. It never throws and never mutates the job.
 */
final class JobInspector
{
    /** @param iterable<Detector> $detectors */
    public function __construct(private readonly iterable $detectors) {}

    /**
     * @param  array<string, mixed>  $context  e.g. ['transaction_level' => 1]
     */
    public function inspect(object $job, array $context = []): GuardReport
    {
        [$bytes, $error] = $this->measure($job);

        $context += [
            'serialized_bytes' => $bytes,
            'serialize_error' => $error,
            'transaction_level' => $context['transaction_level'] ?? 0,
        ];

        $findings = [];

        foreach ($this->detectors as $detector) {
            try {
                foreach ($detector->inspect($job, $context) as $finding) {
                    $findings[] = $finding;
                }
            } catch (Throwable) {
                // A detector must never break a dispatch. Skip it silently.
            }
        }

        return new GuardReport($job::class, $findings, $bytes);
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function measure(object $job): array
    {
        try {
            // serialize() does not mutate the job; __sleep may run but that is
            // exactly what Laravel does when pushing to the queue.
            return [strlen(serialize($job)), null];
        } catch (Throwable $e) {
            return [null, $e->getMessage()];
        }
    }
}
