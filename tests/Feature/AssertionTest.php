<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Feature;

use PHPUnit\Framework\AssertionFailedError;
use Xcodiedev\QueueGuard\Testing\InteractsWithQueueGuard;
use Xcodiedev\QueueGuard\Tests\Fixtures\BigPayloadJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\CleanJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\SensitiveJob;
use Xcodiedev\QueueGuard\Tests\TestCase;

final class AssertionTest extends TestCase
{
    use InteractsWithQueueGuard;

    public function test_assert_passes_for_a_clean_job(): void
    {
        $this->assertJobPassesQueueGuard(new CleanJob(1));
    }

    public function test_assert_fails_for_a_problem_job(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->assertJobPassesQueueGuard(new BigPayloadJob(300));
    }

    public function test_assert_specific_finding_code(): void
    {
        $this->assertJobHasQueueGuardFinding(new SensitiveJob, 'sensitive_property');
    }
}
