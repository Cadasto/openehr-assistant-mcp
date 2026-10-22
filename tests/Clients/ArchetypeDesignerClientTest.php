<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Clients;

use Cadasto\OpenEHR\MCP\Assistant\Apis\ArchetypeDesignerClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

#[CoversClass(ArchetypeDesignerClient::class)]
final class ArchetypeDesignerClientTest extends TestCase
{
    public function testRequestDelegatesToGuzzleClient(): void
    {
        $mockClient = $this->createMock(Client::class);
        $api = new ArchetypeDesignerClient(new NullLogger(), $mockClient);

        $mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'api/repository/list', ['headers' => ['Accept' => 'application/json']])
            ->willReturn(new Response(200, [], '[]'));

        $res = $api->request('GET', 'api/repository/list', ['headers' => ['Accept' => 'application/json']]);
        $this->assertSame('[]', (string)$res->getBody());
    }

    public function testConfigurationAccessors(): void
    {
        $configured = new ArchetypeDesignerClient(new NullLogger(), $this->createStub(Client::class), 'user', 'secret', 'repo-1', 'gh-token');
        $this->assertTrue($configured->isConfigured());
        $this->assertSame('repo-1', $configured->defaultRepositoryId());
        $this->assertSame(['Repository-Token' => 'gh-token'], $configured->repositoryHeaders());

        $unconfigured = new ArchetypeDesignerClient(new NullLogger(), $this->createStub(Client::class), '', '', '', '');
        $this->assertFalse($unconfigured->isConfigured());
        $this->assertSame('', $unconfigured->defaultRepositoryId());
        $this->assertSame([], $unconfigured->repositoryHeaders());
    }

    public function testBuiltClientNeverLogsCredentials(): void
    {
        $logger = new class extends AbstractLogger {
            public string $log = '';

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->log .= $message . json_encode($context);
            }
        };

        new ArchetypeDesignerClient($logger, null, 'user', 'top-secret', 'repo-1', 'gh-token');

        $this->assertStringNotContainsString('top-secret', $logger->log);
        $this->assertStringNotContainsString('gh-token', $logger->log);
    }
}
