<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Xcodiedev\QueueGuard\Exceptions\JobFailedGuardException;
use Xcodiedev\QueueGuard\Tests\Fixtures\BigPayloadJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\CleanJob;
use Xcodiedev\QueueGuard\Tests\TestCase;

final class ListenerTest extends TestCase
{
    use UsesDatabaseQueue;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $this->applyDatabaseQueueConfig($app);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJobsTable();
    }

    public function test_it_logs_findings_when_a_problem_job_is_dispatched(): void
    {
        Log::spy();

        BigPayloadJob::dispatch(300);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message) => str_contains((string) $message, 'queue-guard'))
            ->atLeast()->once();
    }

    public function test_it_stays_silent_for_a_clean_job(): void
    {
        Log::spy();

        CleanJob::dispatch(1);

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    public function test_throw_mode_blocks_the_dispatch(): void
    {
        config()->set('queue-guard.mode', 'throw');

        $this->expectException(JobFailedGuardException::class);

        BigPayloadJob::dispatch(300);
    }

    public function test_ignored_jobs_are_not_inspected(): void
    {
        config()->set('queue-guard.ignore', [BigPayloadJob::class]);
        Log::spy();

        BigPayloadJob::dispatch(300);

        Log::shouldNotHaveReceived('error');
    }
}
