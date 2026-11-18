<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Unit\Class;

use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;
use Wexample\PhpRemote\Class\RemoteGuard;
use Wexample\PhpRemote\Class\RemoteReporter;
use Wexample\PhpRemote\Class\RemoteSignal;
use Wexample\PhpRemote\Enum\RemoteSignalKind;
use Wexample\PhpRemote\Exception\RemoteException;
use Wexample\PhpRemote\Tests\Fixtures\Remote\ReachableRemote;
use Wexample\PhpRemote\Tests\Fixtures\Reporter\RecordingDispatcher;

class RemoteGuardTest extends TestCase
{
    public function testTheCallsResultIsReturnedAndItsRecoveryReported(): void
    {
        $dispatcher = new RecordingDispatcher();

        $this->assertSame(42, (new RemoteGuard(new RemoteReporter($dispatcher)))->attempt(new ReachableRemote(), static fn (): int => 42, 0));
        $this->assertSame(RemoteSignalKind::Recovery, $dispatcher->events[0]->kind);
        $this->assertSame(RemoteGuard::CODE_CALL_FAILED, $dispatcher->events[0]->code);
    }

    public function testAFailingRemoteGivesTheFallbackAndIsReported(): void
    {
        $dispatcher = new RecordingDispatcher();

        $result = (new RemoteGuard(new RemoteReporter($dispatcher)))->attempt(
            new ReachableRemote(),
            static fn (): never => throw new RemoteException('reachable', 'The format changed.'),
            []
        );

        $this->assertSame([], $result);
        $this->assertCount(1, $dispatcher->events);

        $signal = $dispatcher->events[0];
        $this->assertInstanceOf(RemoteSignal::class, $signal);
        $this->assertSame(RemoteSignalKind::Failure, $signal->kind);
        $this->assertSame('reachable', $signal->remoteKey);
        $this->assertSame('The format changed.', $signal->message);
        $this->assertSame(RemoteException::class, $signal->context['exception']);
    }

    public function testAnyExceptionOfTheTransportIsAFailure(): void
    {
        $this->assertNull((new RemoteGuard())->attempt(new ReachableRemote(), static fn (): never => throw new RuntimeException('Connection refused')));
    }

    public function testABugOfTheAppGoesThrough(): void
    {
        $this->expectException(LogicException::class);

        (new RemoteGuard())->attempt(new ReachableRemote(), static fn (): never => throw new LogicException('Wrong call.'));
    }

    public function testAnErrorGoesThrough(): void
    {
        $this->expectException(TypeError::class);

        (new RemoteGuard())->attempt(new ReachableRemote(), static fn (): never => throw new TypeError('Wrong type.'));
    }
}
