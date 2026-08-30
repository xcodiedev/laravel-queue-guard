<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Detectors;

use Xcodiedev\QueueGuard\Finding;

interface Detector
{
    /**
     * Inspect the job and return any findings.
     *
     * Implementations must never throw and must never mutate the job.
     *
     * @param  object  $job  The job instance about to be dispatched.
     * @param  array<string, mixed>  $context  Extra signals, e.g. ['transaction_level' => int].
     * @return list<Finding>
     */
    public function inspect(object $job, array $context): array;
}
