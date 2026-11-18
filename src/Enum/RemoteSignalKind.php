<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Enum;

/**
 * What a remote signals about itself.
 */
enum RemoteSignalKind: string
{
    /** It did not answer, or answered an error: the app went on without it. */
    case Failure = 'failure';

    /**
     * It answered, but something in the answer is wrong — a report dated in the
     * future, a field missing —, and the remote dealt with it as it could.
     */
    case Anomaly = 'anomaly';

    /** What failed under this code works again. */
    case Recovery = 'recovery';
}
