<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Clients;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(CkmClient::class)]
final class CkmClientTest extends TestCase
{
    public function testRequestDelegatesToGuzzleClient(): void
    {
        $logger = new NullLogger();

        $mockClient = $this->createMock(Client::class);
        $api = new CkmClient($logger, $mockClient);

        $mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'v1/archetypes', ['query' => ['q' => 'bp']])
            ->willReturn(new Response(200, [], 'ok'));

        $res = $api->request('GET', 'v1/archetypes', ['query' => ['q' => 'bp']]);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('ok', (string)$res->getBody());
    }

    public function testRequestAsyncDelegatesToGuzzleClient(): void
    {
        $logger = new NullLogger();

        $mockClient = $this->createMock(Client::class);
        $api = new CkmClient($logger, $mockClient);
        $promise = $this->createStub(PromiseInterface::class);

        $mockClient
            ->expects($this->once())
            ->method('requestAsync')
            ->with('POST', 'v1/things', ['json' => ['a' => 1]])
            ->willReturn($promise);

        $p = $api->requestAsync('POST', 'v1/things', ['json' => ['a' => 1]]);
        $this->assertSame($promise, $p);
    }
}
