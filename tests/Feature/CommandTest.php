<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Feature;

use Xcodiedev\QueueGuard\Tests\Fixtures\BigPayloadJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\CleanJob;
use Xcodiedev\QueueGuard\Tests\TestCase;

final class CommandTest extends TestCase
{
    public function test_command_passes_for_a_clean_job(): void
    {
        $this->artisan('queue:guard', ['job' => CleanJob::class])
            ->assertExitCode(0)
            ->expectsOutputToContain('No issues found.');
    }

    public function test_command_fails_for_a_problem_job(): void
    {
        $this->artisan('queue:guard', ['job' => BigPayloadJob::class])
            ->assertExitCode(1);
    }

    public function test_command_reports_unknown_class(): void
    {
        $this->artisan('queue:guard', ['job' => 'App\\Nope'])
            ->assertExitCode(1);
    }

    public function test_json_output_is_valid_json(): void
    {
        $this->artisan('queue:guard', ['job' => BigPayloadJob::class, '--json' => true])
            ->assertExitCode(1);
    }
}
