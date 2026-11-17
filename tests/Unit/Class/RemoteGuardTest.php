<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Unit\Class;

use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;
use TypeError;
use Wexample\PhpRemote\Class\RemoteGuard;
use Wexample\PhpRemote\Exception\RemoteException;
use Wexample\PhpRemote\Tests\Fixtures\Remote\ReachableRemote;

class RemoteGuardTest extends TestCase
{
    public function testTheCallsResultIsReturned(): void
    {
        $this->assertSame(42, (new RemoteGuard())->attempt(new ReachableRemote(), static fn (): int => 42, 0));
    }

    public function testAFailingRemoteGivesTheFallbackAndIsLogged(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{string, string, array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = [(string) $level, (string) $message, $context];
            }
        };

        $result = (new RemoteGuard($logger))->attempt(
            new ReachableRemote(),
            static fn (): never => throw new RemoteException('reachable', 'The format changed.'),
            []
        );

        $this->assertSame([], $result);
        $this->assertCount(1, $logger->records);
        $this->assertSame('warning', $logger->records[0][0]);
        $this->assertSame('reachable', $logger->records[0][2]['remote']);
        $this->assertSame('The format changed.', $logger->records[0][2]['message']);
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
