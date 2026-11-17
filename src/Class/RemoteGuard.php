<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Class;

use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Wexample\PhpRemote\Interface\RemoteInterface;

/**
 * Runs a call to a remote so that its failure does not stop the app: the failure
 * is logged under the remote's key and a fallback is returned in its place — an
 * import goes on with the next item, a page renders without the block.
 *
 * What counts as the remote failing is any `Exception` the call raises: transport
 * errors, error statuses, `RemoteException` for an answer that can no longer be
 * read. A `LogicException` or an `Error` (a `TypeError`…) is the app's own bug
 * and goes through.
 */
final readonly class RemoteGuard
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @template T
     * @template F
     *
     * @param callable(): T $call
     * @param F             $fallback
     *
     * @return T|F
     */
    public function attempt(
        RemoteInterface $remote,
        callable $call,
        mixed $fallback = null
    ): mixed {
        try {
            return $call();
        } catch (LogicException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $this->logger?->warning('Remote "{remote}" failed: {message}', [
                'remote' => $remote->getKey(),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $fallback;
        }
    }
}
