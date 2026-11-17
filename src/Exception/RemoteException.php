<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Exception;

use RuntimeException;
use Throwable;

/**
 * The outside failed: the remote did not answer, answered an error, or answered
 * something that can no longer be read — its format changed. Not a bug of the
 * app: RemoteGuard lets the app go on without what the remote would have given.
 */
class RemoteException extends RuntimeException
{
    public function __construct(
        public readonly string $remoteKey,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
