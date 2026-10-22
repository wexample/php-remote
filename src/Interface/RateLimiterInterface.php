<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Interface;

/**
 * A request quota, which the framework integrating this package implements
 * with its own storage, so that it can count across processes.
 */
interface RateLimiterInterface
{
    /**
     * Returns once the quota grants one more request, waiting as long as it takes.
     */
    public function acquire(): void;
}
