<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Detectors;

use Xcodiedev\QueueGuard\Finding;
use Xcodiedev\QueueGuard\Severity;
use Xcodiedev\QueueGuard\Support\PropertyWalker;

/**
 * Flags job properties that appear to carry secrets or PII. Queue payloads are
 * stored in plain text on the driver (database, Redis, SQS) and in failed_jobs;
 * keeping raw secrets there widens the blast radius of any leak.
 *
 * Detection is name-based plus a Luhn check for card-shaped strings. Values are
 * never copied into the finding.
 */
final class SensitiveDataDetector implements Detector
{
    /**
     * @param  list<string>  $nameDenylist  Lower-case substrings matched against property names.
     */
    public function __construct(
        private readonly PropertyWalker $walker = new PropertyWalker,
        private readonly array $nameDenylist = [
            'password', 'passwd', 'secret', 'token', 'api_key', 'apikey',
            'authorization', 'auth_token', 'access_key', 'private_key',
            'card_number', 'cardnumber', 'cvv', 'cvc', 'ssn',
        ],
    ) {}

    public function inspect(object $job, array $context): array
    {
        $findings = [];
        $seenNamePaths = [];

        $this->walker->walk($job, function (string $path, mixed $value) use (&$findings, &$seenNamePaths): void {
            if ($path === '') {
                return;
            }

            $segment = strtolower((string) preg_replace('/[^a-z0-9]/i', '_', self::lastSegment($path)));

            foreach ($this->nameDenylist as $needle) {
                if (str_contains($segment, $needle) && ! in_array($path, $seenNamePaths, true)) {
                    $seenNamePaths[] = $path;
                    $findings[] = new Finding(
                        Severity::Warning,
                        'sensitive_property',
                        sprintf('Property name looks sensitive ("%s"); its value will sit in the queue payload in plain text.', $needle),
                        $path,
                        'Pass a reference (user id, credential id) and fetch the secret inside handle().',
                    );
                    break;
                }
            }

            if (is_string($value) && self::looksLikeCard($value)) {
                $findings[] = new Finding(
                    Severity::Warning,
                    'sensitive_value',
                    'Property value is a card-shaped number that passes the Luhn check.',
                    $path,
                    'Never queue raw card data; use a payment token.',
                );
            }
        });

        return $findings;
    }

    private static function lastSegment(string $path): string
    {
        $parts = explode('.', $path);

        return (string) end($parts);
    }

    private static function looksLikeCard(string $value): bool
    {
        $digits = preg_replace('/[ -]/', '', $value);

        if ($digits === null || ! ctype_digit($digits) || strlen($digits) < 13 || strlen($digits) > 19) {
            return false;
        }

        $sum = 0;
        $alt = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = ! $alt;
        }

        return $sum % 10 === 0;
    }
}
