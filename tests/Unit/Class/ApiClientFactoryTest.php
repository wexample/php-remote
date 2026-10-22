<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Unit\Class;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Wexample\PhpApi\Common\ClientOptions;
use Wexample\PhpRemote\Class\ApiClientFactory;
use Wexample\PhpRemote\Class\ClientDefinition;
use Wexample\PhpRemote\Tests\Fixtures\Client\DemoApiClient;
use Wexample\PhpRemote\Tests\Fixtures\RateLimiter\CountingRateLimiter;

class ApiClientFactoryTest extends TestCase
{
    private MockHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new MockHandler();
    }

    public function testTheClientIsBuiltFromItsDefinition(): void
    {
        $client = $this->factory()->create(new ClientDefinition(
            key: 'demo',
            label: 'Demo API',
            class: DemoApiClient::class,
            baseUrl: 'https://demo.test',
            apiKey: 'secret',
            apiKeyRequired: true,
            headers: ['X-Test' => 'yes', 'X-Empty' => ''],
            options: new ClientOptions(timeout: 30.0),
        ));
        $this->handler->append(new Response(200));

        $client->get('things');

        $request = $this->handler->getLastRequest();
        $this->assertInstanceOf(DemoApiClient::class, $client);
        $this->assertSame('https://demo.test/things', (string) $request->getUri());
        $this->assertSame('Bearer secret', $request->getHeaderLine('Authorization'));
        $this->assertSame('yes', $request->getHeaderLine('X-Test'));
        $this->assertFalse($request->hasHeader('X-Empty'));
        $this->assertSame(30.0, $this->handler->getLastOptions()['timeout']);
    }

    public function testTheRateLimiterIsAskedBeforeEachRequest(): void
    {
        $limiter = new CountingRateLimiter();
        $client = $this->factory()->create(
            new ClientDefinition(key: 'limited', label: 'Limited', baseUrl: 'https://limited.test'),
            $limiter
        );
        $this->handler->append(new Response(200), new Response(200));

        $client->get('first');
        $client->get('second');

        $this->assertSame(2, $limiter->acquired);
    }

    private function factory(): ApiClientFactory
    {
        return new ApiClientFactory(HandlerStack::create($this->handler));
    }
}
