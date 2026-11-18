<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Class;

use Exception;
use LogicException;
use Wexample\PhpRemote\Interface\RemoteInterface;

/**
 * Runs a call to a remote so that its failure does not stop the app: the failure
 * is reported (`call_failed`) under the remote's key and a fallback is returned in
 * its place — an import goes on with the next item, a page renders without the
 * block. A call that works reports the recovery, which closes what an earlier
 * failure opened.
 *
 * What counts as the remote failing is any `Exception` the call raises: transport
 * errors, error statuses, `RemoteException` for an answer that can no longer be
 * read. A `LogicException` or an `Error` (a `TypeError`…) is the app's own bug
 * and goes through.
 */
final readonly class RemoteGuard
{
    public const string CODE_CALL_FAILED = 'call_failed';

    public function __construct(
        private RemoteReporter $reporter = new RemoteReporter(),
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
            $result = $call();
        } catch (LogicException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $this->reporter->failure($remote->getKey(), self::CODE_CALL_FAILED, $exception->getMessage(), ['exception' => $exception::class]);

            return $fallback;
        }

        $this->reporter->recovery($remote->getKey(), self::CODE_CALL_FAILED);

        return $result;
    }
}
