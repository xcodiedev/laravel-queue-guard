<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Xcodiedev\QueueGuard\Tests\Fixtures\BigPayloadJob;
use Xcodiedev\QueueGuard\Tests\TestCase;

final class DisabledEnvironmentTest extends TestCase
{
    use UsesDatabaseQueue;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('queue-guard.environments', ['production']);
        $this->applyDatabaseQueueConfig($app);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJobsTable();
    }

    public function test_automatic_listener_is_inactive_outside_configured_environments(): void
    {
        Log::spy();

        BigPayloadJob::dispatch(300);

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }
}
