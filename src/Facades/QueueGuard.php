<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Facades;

use Illuminate\Support\Facades\Facade;
use Xcodiedev\QueueGuard\GuardReport;

/**
 * @method static GuardReport inspect(object $job, array<string, mixed> $context = [])
 * @method static GuardReport guard(object $job, array<string, mixed> $context = [])
 *
 * @see \Xcodiedev\QueueGuard\QueueGuard
 */
final class QueueGuard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Xcodiedev\QueueGuard\QueueGuard::class;
    }
}
