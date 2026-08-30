<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard\Tests\Fixtures;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CleanJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $orderId = 1) {}

    public function handle(): void {}
}

final class BigPayloadJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public string $blob;

    public function __construct(int $kb = 300)
    {
        $this->blob = str_repeat('a', $kb * 1024);
    }

    public function handle(): void {}
}

final class ClosureJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /** @var Closure */
    public $callback;

    public function __construct()
    {
        $this->callback = static fn () => 'nope';
    }

    public function handle(): void {}
}

final class ResourceJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /** @var resource */
    public $handle;

    public function __construct()
    {
        $this->handle = fopen('php://memory', 'rb');
    }

    public function handle(): void {}
}

final class BinaryStringJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public string $image;

    public function __construct()
    {
        $this->image = "\xff\xd8\xff\xe0\x00\x10JFIF\x00\x01".random_bytes(32);
    }

    public function handle(): void {}
}

final class SensitiveJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        public string $apiToken = 'super-secret-value',
        public string $cardNumber = '4111111111111111',
    ) {}

    public function handle(): void {}
}

final class NestedSensitiveJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /** @var array<string, mixed> */
    public array $context;

    public function __construct()
    {
        $this->context = ['user' => ['id' => 5, 'password' => 'hunter2']];
    }

    public function handle(): void {}
}
