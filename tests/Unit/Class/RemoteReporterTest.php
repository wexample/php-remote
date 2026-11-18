<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Unit\Class;

use PHPUnit\Framework\TestCase;
use Wexample\PhpRemote\Class\RemoteReporter;
use Wexample\PhpRemote\Enum\RemoteSignalKind;
use Wexample\PhpRemote\Tests\Fixtures\Reporter\RecordingDispatcher;
use Wexample\PhpRemote\Tests\Fixtures\Reporter\RecordingLogger;

class RemoteReporterTest extends TestCase
{
    public function testAnAnomalyIsDispatchedAndLoggedWithItsContext(): void
    {
        $dispatcher = new RecordingDispatcher();
        $logger = new RecordingLogger();

        (new RemoteReporter($dispatcher, $logger))->anomaly('senat_debates', 'misdated_report', 'Dated after today.', ['file' => 'd20261204.xml'], 'd20261204.xml');

        $signal = $dispatcher->events[0];
        $this->assertSame(RemoteSignalKind::Anomaly, $signal->kind);
        $this->assertSame('misdated_report', $signal->code);
        $this->assertSame('d20261204.xml', $signal->fingerprint);

        [$level, , $context] = $logger->records[0];
        $this->assertSame('notice', $level);
        $this->assertSame('senat_debates', $context['remote']);
        $this->assertSame('d20261204.xml', $context['file']);
    }

    public function testAFailureIsAWarning(): void
    {
        $logger = new RecordingLogger();

        (new RemoteReporter(logger: $logger))->failure('dila_an_debates', 'call_failed', 'Timeout.');

        $this->assertSame('warning', $logger->records[0][0]);
    }

    public function testARecoveryIsDispatchedButNotLogged(): void
    {
        $dispatcher = new RecordingDispatcher();
        $logger = new RecordingLogger();

        (new RemoteReporter($dispatcher, $logger))->recovery('dila_an_debates', 'call_failed');

        $this->assertCount(1, $dispatcher->events);
        $this->assertSame([], $logger->records);
    }
}
