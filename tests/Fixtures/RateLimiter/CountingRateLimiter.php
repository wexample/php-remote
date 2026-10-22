<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Fixtures\RateLimiter;

use Wexample\PhpRemote\Interface\RateLimiterInterface;

class CountingRateLimiter implements RateLimiterInterface
{
    public int $acquired = 0;

    public function acquire(): void
    {
        $this->acquired++;
    }
}
