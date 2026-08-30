<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard;

use Countable;

final class GuardReport implements Countable
{
    /** @param list<Finding> $findings */
    public function __construct(
        public readonly string $job,
        public readonly array $findings = [],
        /** Serialized payload size in bytes, or null if it could not be measured. */
        public readonly ?int $payloadBytes = null,
    ) {}

    public function passes(): bool
    {
        return ! $this->hasErrors();
    }

    public function hasErrors(): bool
    {
        return $this->errors() !== [];
    }

    public function hasWarnings(): bool
    {
        return $this->warnings() !== [];
    }

    /** @return list<Finding> */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->findings,
            static fn (Finding $f): bool => $f->severity === Severity::Error,
        ));
    }

    /** @return list<Finding> */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->findings,
            static fn (Finding $f): bool => $f->severity === Severity::Warning,
        ));
    }

    public function count(): int
    {
        return count($this->findings);
    }

    /** @return array{job: string, payload_bytes: int|null, findings: list<array<string, string|null>>} */
    public function toArray(): array
    {
        return [
            'job' => $this->job,
            'payload_bytes' => $this->payloadBytes,
            'findings' => array_map(static fn (Finding $f): array => $f->toArray(), $this->findings),
        ];
    }
}
