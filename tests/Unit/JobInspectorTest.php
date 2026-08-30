<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Xcodiedev\QueueGuard\Detectors\BinaryStringDetector;
use Xcodiedev\QueueGuard\Detectors\Detector;
use Xcodiedev\QueueGuard\Detectors\PayloadSizeDetector;
use Xcodiedev\QueueGuard\Detectors\SensitiveDataDetector;
use Xcodiedev\QueueGuard\Detectors\TransactionSafetyDetector;
use Xcodiedev\QueueGuard\Detectors\UnserializablePropertyDetector;
use Xcodiedev\QueueGuard\GuardReport;
use Xcodiedev\QueueGuard\JobInspector;
use Xcodiedev\QueueGuard\Severity;
use Xcodiedev\QueueGuard\Tests\Fixtures\BigPayloadJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\BinaryStringJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\CleanJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\ClosureJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\NestedSensitiveJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\ResourceJob;
use Xcodiedev\QueueGuard\Tests\Fixtures\SensitiveJob;

final class JobInspectorTest extends TestCase
{
    private function inspector(): JobInspector
    {
        return new JobInspector([
            new PayloadSizeDetector(65536, 262144),
            new UnserializablePropertyDetector,
            new BinaryStringDetector,
            new SensitiveDataDetector,
            new TransactionSafetyDetector,
        ]);
    }

    private function codes(GuardReport $r): array
    {
        return array_map(static fn ($f) => $f->code, $r->findings);
    }

    public function test_clean_job_passes(): void
    {
        $report = $this->inspector()->inspect(new CleanJob(42));

        $this->assertTrue($report->passes());
        $this->assertSame([], $report->findings);
        $this->assertIsInt($report->payloadBytes);
    }

    public function test_large_payload_is_an_error(): void
    {
        $report = $this->inspector()->inspect(new BigPayloadJob(300));

        $this->assertFalse($report->passes());
        $this->assertContains('payload_size', $this->codes($report));
        $this->assertSame(Severity::Error, $report->errors()[0]->severity);
    }

    public function test_closure_property_is_flagged(): void
    {
        $report = $this->inspector()->inspect(new ClosureJob);

        $this->assertContains('unserializable_property', $this->codes($report));
        $this->assertFalse($report->passes());
        $this->assertSame('callback', $report->errors()[0]->property);
    }

    public function test_resource_property_is_flagged(): void
    {
        $report = $this->inspector()->inspect(new ResourceJob);

        $this->assertContains('unserializable_property', $this->codes($report));
    }

    public function test_binary_string_is_a_warning(): void
    {
        $report = $this->inspector()->inspect(new BinaryStringJob);

        $this->assertContains('binary_string', $this->codes($report));
        $this->assertTrue($report->hasWarnings());
    }

    public function test_sensitive_property_name_and_card_value_are_flagged(): void
    {
        $report = $this->inspector()->inspect(new SensitiveJob);
        $codes = $this->codes($report);

        $this->assertContains('sensitive_property', $codes);
        $this->assertContains('sensitive_value', $codes);
    }

    public function test_nested_sensitive_key_is_found(): void
    {
        $report = $this->inspector()->inspect(new NestedSensitiveJob);

        $this->assertContains('sensitive_property', $this->codes($report));
        $this->assertSame('context.user.password', $report->findings[0]->property);
    }

    public function test_findings_never_contain_the_value(): void
    {
        $report = $this->inspector()->inspect(new SensitiveJob(apiToken: 'super-secret-value'));

        foreach ($report->findings as $finding) {
            $this->assertStringNotContainsString('super-secret-value', $finding->message);
            $this->assertStringNotContainsString('super-secret-value', (string) $finding->hint);
        }
    }

    public function test_transaction_context_produces_warning(): void
    {
        $report = $this->inspector()->inspect(new CleanJob, ['transaction_level' => 2]);

        $this->assertContains('dispatch_in_transaction', $this->codes($report));
    }

    public function test_detector_exceptions_do_not_break_inspection(): void
    {
        $boom = new class implements Detector
        {
            public function inspect(object $job, array $context): array
            {
                throw new \RuntimeException('boom');
            }
        };

        $report = (new JobInspector([$boom, new PayloadSizeDetector]))->inspect(new CleanJob);

        $this->assertTrue($report->passes());
    }
}
