<?php

declare(strict_types=1);

namespace Xcodiedev\QueueGuard;

enum Severity: string
{
    case Warning = 'warning';
    case Error = 'error';
}
