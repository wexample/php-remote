<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Class;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Wexample\PhpApi\Common\Client;
use Wexample\PhpRemote\Interface\RateLimiterInterface;

/**
 * Builds php-api clients from their definition. It builds the Guzzle client
 * itself, so that a rate limiter can join its handler stack.
 */
class ApiClientFactory
{
    public const string MIDDLEWARE_RATE_LIMIT = 'wexample_remote_rate_limit';

    /**
     * @param HandlerStack|null $handlerStack Guzzle handlers to build on; tests pass a mock.
     */
    public function __construct(
        private readonly ?HandlerStack $handlerStack = null,
    ) {
    }

    public function create(
        ClientDefinition $definition,
        ?RateLimiterInterface $rateLimiter = null,
    ): Client {
        $baseUrl = rtrim((string) $definition->baseUrl, '/').'/';
        $stack = $this->handlerStack ? clone $this->handlerStack : HandlerStack::create();

        if ($rateLimiter) {
            $stack->push(new RateLimitMiddleware($rateLimiter), self::MIDDLEWARE_RATE_LIMIT);
        }

        $class = $definition->class;

        return new $class(
            $baseUrl,
            '' === $definition->apiKey ? null : $definition->apiKey,
            new GuzzleClient(['base_uri' => $baseUrl, 'handler' => $stack]),
            $definition->getHeaders(),
            $definition->options,
        );
    }
}
