<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Xcodiedev\QueueGuard\Support\PropertyWalker;

final class PropertyWalkerTest extends TestCase
{
    public function test_it_respects_the_node_limit(): void
    {
        $root = (object) ['items' => range(1, 5000)];

        $visited = 0;
        (new PropertyWalker(maxDepth: 10, maxNodes: 100))
            ->walk($root, function () use (&$visited): void {
                $visited++;
            });

        $this->assertLessThanOrEqual(100, $visited);
    }

    public function test_it_respects_the_depth_limit(): void
    {
        $deep = (object) [];
        $cursor = $deep;
        for ($i = 0; $i < 20; $i++) {
            $cursor->child = (object) [];
            $cursor = $cursor->child;
        }
        $cursor->marker = 'deep-value';

        $seen = [];
        (new PropertyWalker(maxDepth: 3, maxNodes: 1000))
            ->walk($deep, function (string $path, mixed $value) use (&$seen): void {
                $seen[] = $path;
            });

        $this->assertNotContains('child.child.child.child.marker', $seen);
    }

    public function test_it_handles_circular_references(): void
    {
        $a = (object) [];
        $b = (object) [];
        $a->b = $b;
        $b->a = $a;

        $count = 0;
        (new PropertyWalker)->walk($a, function () use (&$count): void {
            $count++;
        });

        $this->assertGreaterThan(0, $count);
        $this->assertLessThan(50, $count);
    }
}
