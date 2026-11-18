<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Fixtures\Reporter;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Keeps what is logged, for a test to read.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{string, string, array<string, mixed>}> */
    public array $records = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [(string) $level, (string) $message, $context];
    }
}
