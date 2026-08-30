<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Xcodiedev\QueueGuard\QueueGuardServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [QueueGuardServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('queue-guard.environments', ['testing']);
    }
}
