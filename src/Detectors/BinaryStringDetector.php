<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Detectors;

use Xcodiedev\QueueGuard\Finding;
use Xcodiedev\QueueGuard\Severity;
use Xcodiedev\QueueGuard\Support\PropertyWalker;

/**
 * Flags string properties that are not valid UTF-8 (raw image bytes, gzip
 * output, encrypted blobs). Laravel JSON-encodes the payload, which corrupts
 * such data silently.
 */
final class BinaryStringDetector implements Detector
{
    public function __construct(private readonly PropertyWalker $walker = new PropertyWalker) {}

    public function inspect(object $job, array $context): array
    {
        $findings = [];

        $this->walker->walk($job, function (string $path, mixed $value) use (&$findings): void {
            if ($path === '' || ! is_string($value) || $value === '') {
                return;
            }

            if (! mb_check_encoding($value, 'UTF-8')) {
                $findings[] = new Finding(
                    Severity::Warning,
                    'binary_string',
                    sprintf('Property holds %d bytes of non-UTF-8 data; JSON encoding of the payload will corrupt it.', strlen($value)),
                    $path,
                    'base64-encode the bytes, or store them on disk/S3 and pass a reference.',
                );
            }
        });

        return $findings;
    }
}
