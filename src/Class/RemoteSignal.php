<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Class;

use DateTimeImmutable;
use Wexample\PhpRemote\Enum\RemoteSignalKind;

/**
 * What a remote tells about itself, in the one shape every remote uses: a
 * failure, an anomaly in what it answered, a recovery. RemoteReporter sends it
 * to whoever listens — a log, an incident tracker, a history.
 */
final readonly class RemoteSignal
{
    /**
     * @param string                     $code        stable, what a listener groups and a translation reads:
     *                                                `call_failed`, `down`, `misdated_report`
     * @param array<string, scalar|null> $context     what it takes to understand it later
     * @param string|null                $fingerprint what tells two signals of the same code apart — the file
     *                                                a misdated report is in —; null when the code is enough
     */
    public function __construct(
        public string $remoteKey,
        public RemoteSignalKind $kind,
        public string $code,
        public string $message = '',
        public array $context = [],
        public ?string $fingerprint = null,
        public DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
    }
}
