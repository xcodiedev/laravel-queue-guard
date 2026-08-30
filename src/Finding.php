<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard;

/**
 * A single issue discovered while inspecting a job.
 *
 * Findings never contain the offending value itself, only its location
 * (property path) and a human-readable explanation. This keeps reports safe
 * to log.
 */
final class Finding
{
    public function __construct(
        public readonly Severity $severity,
        /** Stable machine-readable identifier, e.g. "payload_size". */
        public readonly string $code,
        /** What is wrong. */
        public readonly string $message,
        /** Dot path to the offending property, or null when job-wide. */
        public readonly ?string $property = null,
        /** Optional remediation advice. */
        public readonly ?string $hint = null,
    ) {}

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity->value,
            'code' => $this->code,
            'message' => $this->message,
            'property' => $this->property,
            'hint' => $this->hint,
        ];
    }
}
