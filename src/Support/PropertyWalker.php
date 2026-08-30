<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Support;

use Closure;
use ReflectionObject;

/**
 * Depth- and node-limited walk over a job's property graph.
 *
 * The limits are a safety measure: an attacker-controlled or accidentally huge
 * object graph must not be able to make inspection run away.
 */
final class PropertyWalker
{
    public function __construct(
        private readonly int $maxDepth = 6,
        private readonly int $maxNodes = 2000,
    ) {}

    /**
     * @param  Closure(string $path, mixed $value): void  $visit
     */
    public function walk(object $root, Closure $visit): void
    {
        $nodes = 0;
        /** @var list<object> $seen */
        $seen = [];

        $recurse = function (mixed $value, string $path, int $depth) use (&$recurse, &$nodes, &$seen, $visit): void {
            if ($nodes >= $this->maxNodes || $depth > $this->maxDepth) {
                return;
            }
            $nodes++;

            $visit($path, $value);

            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $recurse($item, $path === '' ? (string) $key : $path.'.'.$key, $depth + 1);
                }

                return;
            }

            if (is_object($value)) {
                foreach ($seen as $prev) {
                    if ($prev === $value) {
                        return;
                    }
                }
                $seen[] = $value;

                // Closures / internal objects expose no useful public props.
                if ($value instanceof Closure) {
                    return;
                }

                foreach ((new ReflectionObject($value))->getProperties() as $property) {
                    if ($property->isStatic()) {
                        continue;
                    }
                    $property->setAccessible(true);
                    if (! $property->isInitialized($value)) {
                        continue;
                    }
                    $recurse(
                        $property->getValue($value),
                        $path === '' ? $property->getName() : $path.'.'.$property->getName(),
                        $depth + 1,
                    );
                }
            }
        };

        $recurse($root, '', 0);
    }
}
