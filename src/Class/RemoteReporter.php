<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Class;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Wexample\PhpRemote\Enum\RemoteSignalKind;

/**
 * Where a remote says what happened to it: each RemoteSignal is dispatched as
 * a PSR-14 event — the signal itself is the event — and written to the log,
 * a failure as a warning, an anomaly as a notice. A recovery is dispatched
 * only: it comes with every call that works.
 *
 * Without a dispatcher and a logger, nothing is told: a remote reports in its
 * code whatever the app listens to.
 */
final readonly class RemoteReporter
{
    public function __construct(
        private ?EventDispatcherInterface $dispatcher = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function report(RemoteSignal $signal): void
    {
        if (RemoteSignalKind::Recovery !== $signal->kind) {
            $this->logger?->log(
                RemoteSignalKind::Failure === $signal->kind ? 'warning' : 'notice',
                'Remote "{remote}", {code}: {message}',
                ['remote' => $signal->remoteKey, 'code' => $signal->code, 'message' => $signal->message] + $signal->context
            );
        }

        $this->dispatcher?->dispatch($signal);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function failure(
        string $remoteKey,
        string $code,
        string $message,
        array $context = [],
        ?string $fingerprint = null
    ): void {
        $this->report(new RemoteSignal($remoteKey, RemoteSignalKind::Failure, $code, $message, $context, $fingerprint));
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function anomaly(
        string $remoteKey,
        string $code,
        string $message,
        array $context = [],
        ?string $fingerprint = null
    ): void {
        $this->report(new RemoteSignal($remoteKey, RemoteSignalKind::Anomaly, $code, $message, $context, $fingerprint));
    }

    /**
     * What failed under $code works again.
     */
    public function recovery(
        string $remoteKey,
        string $code,
        ?string $fingerprint = null
    ): void {
        $this->report(new RemoteSignal($remoteKey, RemoteSignalKind::Recovery, $code, fingerprint: $fingerprint));
    }
}
