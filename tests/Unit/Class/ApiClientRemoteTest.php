<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Unit\Class;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Wexample\PhpRemote\Class\ApiClientFactory;
use Wexample\PhpRemote\Class\ApiClientRemote;
use Wexample\PhpRemote\Class\ClientDefinition;
use Wexample\PhpRemote\Class\RemoteRegistry;
use Wexample\PhpRemote\Class\RemoteStatus;
use Wexample\PhpRemote\Enum\RemoteState;
use Wexample\PhpRemote\Tests\Fixtures\Client\DemoApiClient;

class ApiClientRemoteTest extends TestCase
{
    private MockHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new MockHandler();
    }

    public function testAnAnsweringClientIsUp(): void
    {
        $this->handler->append(new Response(200));

        $status = $this->check(new ClientDefinition(
            key: 'demo',
            label: 'Demo API',
            class: DemoApiClient::class,
            baseUrl: 'https://demo.test',
        ));

        $this->assertSame(RemoteState::Up, $status->state);
        $this->assertSame('/health', $this->handler->getLastRequest()->getUri()->getPath());
    }

    public function testAFailingClientIsDownWithTheReason(): void
    {
        $this->handler->append(new Response(503));

        $status = $this->check(new ClientDefinition(key: 'demo', label: 'Demo API', baseUrl: 'https://demo.test'));

        $this->assertSame(RemoteState::Down, $status->state);
        $this->assertStringContainsString('HTTP 503', $status->message);
    }

    public function testMissingSettingsLeaveTheRemoteUnconfigured(): void
    {
        $status = $this->check(new ClientDefinition(key: 'demo', label: 'Demo API', apiKeyRequired: true));

        $this->assertSame(RemoteState::Unconfigured, $status->state);
        $this->assertSame('Missing: base_url, api_key.', $status->message);
        $this->assertCount(0, $this->handler);
    }

    private function check(ClientDefinition $definition): RemoteStatus
    {
        $client = (new ApiClientFactory(HandlerStack::create($this->handler)))->create($definition);

        return (new RemoteRegistry([new ApiClientRemote($definition, $client)]))->check($definition->key);
    }
}
