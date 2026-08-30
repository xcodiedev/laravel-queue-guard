<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Detectors;

use Closure;
use Generator;
use Xcodiedev\QueueGuard\Finding;
use Xcodiedev\QueueGuard\Severity;
use Xcodiedev\QueueGuard\Support\PropertyWalker;

/**
 * Flags values that cannot survive queue serialization: closures, resources
 * (stream/curl handles), PDO connections and generators. These make the job
 * throw the moment it is pushed.
 */
final class UnserializablePropertyDetector implements Detector
{
    public function __construct(private readonly PropertyWalker $walker = new PropertyWalker) {}

    public function inspect(object $job, array $context): array
    {
        $findings = [];

        // serialize() itself already failed elsewhere; still pinpoint the cause.
        $error = $context['serialize_error'] ?? null;

        $this->walker->walk($job, function (string $path, mixed $value) use (&$findings): void {
            $type = match (true) {
                $value instanceof Closure => 'a Closure',
                is_resource($value) => 'a resource ('.get_resource_type($value).')',
                $value instanceof Generator => 'a Generator',
                $value instanceof \PDO => 'a PDO connection',
                $value instanceof \PDOStatement => 'a PDOStatement',
                default => null,
            };

            if ($type !== null && $path !== '') {
                $findings[] = new Finding(
                    Severity::Error,
                    'unserializable_property',
                    sprintf('Property holds %s, which cannot be serialized onto the queue.', $type),
                    $path,
                    'Store the data by reference (id, path) and rebuild it inside handle().',
                );
            }
        });

        if ($findings === [] && is_string($error)) {
            $findings[] = new Finding(
                Severity::Error,
                'unserializable_property',
                'The job cannot be serialized: '.$error,
                null,
                'Remove closures, resources or other non-serializable values from the job properties.',
            );
        }

        return $findings;
    }
}
