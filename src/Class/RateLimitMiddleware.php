<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Class;

use Psr\Http\Message\RequestInterface;
use Wexample\PhpRemote\Interface\RateLimiterInterface;

/**
 * Guzzle middleware holding each request until the limiter grants it. It sits
 * in the handler stack rather than in a Client subclass, so it fits any client.
 */
final readonly class RateLimitMiddleware
{
    public function __construct(
        private RateLimiterInterface $limiter,
    ) {
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $this->limiter->acquire();

            return $handler($request, $options);
        };
    }
}
