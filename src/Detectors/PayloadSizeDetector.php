<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Detectors;

use Xcodiedev\QueueGuard\Finding;
use Xcodiedev\QueueGuard\Severity;

/**
 * Flags jobs whose serialized payload is large. Big payloads slow every worker
 * fetch and, on Amazon SQS, are rejected above 256 KB.
 */
final class PayloadSizeDetector implements Detector
{
    public function __construct(
        private readonly int $warnBytes = 65536,
        private readonly int $errorBytes = 262144,
    ) {}

    public function inspect(object $job, array $context): array
    {
        $bytes = $context['serialized_bytes'] ?? null;

        if (! is_int($bytes)) {
            return [];
        }

        if ($bytes >= $this->errorBytes) {
            return [new Finding(
                Severity::Error,
                'payload_size',
                sprintf('Serialized payload is %s, over the %s limit (Amazon SQS rejects messages above 256 KB).', self::human($bytes), self::human($this->errorBytes)),
                null,
                'Pass identifiers instead of full objects and reload them inside handle().',
            )];
        }

        if ($bytes >= $this->warnBytes) {
            return [new Finding(
                Severity::Warning,
                'payload_size',
                sprintf('Serialized payload is %s, above the %s soft limit.', self::human($bytes), self::human($this->warnBytes)),
                null,
                'Consider passing identifiers instead of full objects.',
            )];
        }

        return [];
    }

    private static function human(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        return round($bytes / 1024, 1).' KB';
    }
}
