<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Exceptions;

use RuntimeException;
use Xcodiedev\QueueGuard\GuardReport;

final class JobFailedGuardException extends RuntimeException
{
    public function __construct(public readonly GuardReport $report)
    {
        $lines = [];
        foreach ($report->findings as $finding) {
            $lines[] = sprintf(
                '[%s] %s%s',
                strtoupper($finding->severity->value),
                $finding->property !== null ? $finding->property.': ' : '',
                $finding->message,
            );
        }

        parent::__construct(
            sprintf("Queue Guard blocked %s:\n%s", $report->job, implode("\n", $lines)),
        );
    }
}
