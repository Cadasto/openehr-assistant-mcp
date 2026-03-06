<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Tools\CkmService;
use GuzzleHttp\Psr7\Response;
use Mcp\Schema\Content\TextContent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\NullLogger;

#[CoversClass(CkmService::class)]
final class CkmServiceTest extends TestCase
{
    private CkmClient $client;
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->client = $this->createMock(CkmClient::class);
        $this->logger = new NullLogger();
    }

    public function testArchetypeListSendsQueryAndDecodesJson(): void
    {
        $payload = [
            ['resourceMainId' => 'openEHR-EHR-OBSERVATION.blood_pressure.v1', 'cid' => '123.45a'],
            ['resourceMainId' => 'openEHR-EHR-OBSERVATION.body_weight.v1', 'cid' => '678.90b'],
        ];

        $this->client
            ->expects($this->once())
            ->method('get')
            ->with(
                'v1/archetypes',
                $this->callback(function (array $opts): bool {
                    $q = $opts['query'] ?? [];
                    $headers = $opts['headers'] ?? [];
                    return ($q['search-text'] ?? null) === 'blood' && ($q['restrict-search-to-main-data'] ?? null) === 'true'
                        && ($headers['Accept'] ?? null) === 'application/json';
                })
            )
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->archetypeSearch('blood');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(2, $result['items']);
        $this->assertArrayHasKey('cid', $result['items'][0]);
        $this->assertSame($payload[0]['cid'], $result['items'][0]['cid']);
        $this->assertArrayHasKey('archetypeId', $result['items'][0]);
        $this->assertSame($payload[0]['resourceMainId'], $result['items'][0]['archetypeId']);
    }

    public function testArchetypeGetRespectsFormatAndReturnsTextContent(): void
    {
        // format "adl" -> Map::archetypeFormat('adl') returns 'adl' and contentType 'text/plain' via Map::contentType
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->callback(function (string $endpoint): bool {
                    // CID is sanitized: non-digits replaced with '-'. For '123.45a', becomes '123.45-'
                    // The service then requests v1/archetypes/{cid}/{format}
                    return str_starts_with($endpoint, 'v1/archetypes/123.45-') && str_ends_with($endpoint, '/adl');
                }),
                $this->callback(function (array $opts): bool {
                    return ($opts['headers']['Accept'] ?? null) === 'text/plain';
                })
            )
            ->willReturn(new Response(200, ['Content-Type' => 'text/plain'], 'archetype ADL content'));

        $svc = new CkmService($this->client, $this->logger);
        $content = $svc->archetypeGet('123.45a', 'adl');
        $this->assertInstanceOf(TextContent::class, $content);
        $this->assertStringContainsString('archetype ADL content', $content->text);
        $this->assertStringContainsString('```', $content->text);
    }

    public function testArchetypeSearchFetchesMoreThenSlicesToMaxResults(): void
    {
        $maxResults = 10;
        $fetchSize = (int) ceil($maxResults * 1.5); // 15
        $payload = array_fill(0, $fetchSize, ['cid' => '1.0.0', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.foo.v1', 'resourceMainDisplayName' => 'Foo']);

        $capturedQuery = null;
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with(
                'v1/archetypes',
                $this->callback(function (array $opts) use (&$capturedQuery): bool {
                    $capturedQuery = $opts['query'] ?? [];
                    return true;
                })
            )
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->archetypeSearch('foo', $maxResults);

        $this->assertSame($fetchSize, (int) ($capturedQuery['size'] ?? 0), 'Request size should be 1.5× maxResults for ranking');
        $this->assertSame(0, (int) ($capturedQuery['offset'] ?? -1), 'Request offset should always be 0');
        $this->assertCount($maxResults, $result['items'], 'Returned items should be sliced to maxResults');
    }

    public function testArchetypeSearchCapsFetchSizeAtMaxResultsLimit(): void
    {
        $maxResults = 50; // MAX_RESULTS_LIMIT
        $fetchSize = min(50, (int) ceil($maxResults * 1.5)); // 50 (cap)

        $capturedQuery = null;
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with('v1/archetypes', $this->callback(function (array $opts) use (&$capturedQuery): bool {
                $capturedQuery = $opts['query'] ?? [];
                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], '[]'));

        $svc = new CkmService($this->client, $this->logger);
        $svc->archetypeSearch('x', $maxResults);

        $this->assertSame(50, (int) ($capturedQuery['size'] ?? 0));
        $this->assertSame(0, (int) ($capturedQuery['offset'] ?? -1));
    }

    public function testExceptionsAreWrappedAsRuntimeException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements ClientExceptionInterface {
        };

        $this->client
            ->expects($this->once())
            ->method('get')
            ->with('v1/archetypes', $this->anything())
            ->willThrowException($exception);

        $svc = new CkmService($this->client, $this->logger);
        $this->expectException(\RuntimeException::class);
        $svc->archetypeSearch('x');
    }

    public function testTemplateSearchSendsQueryAndDecodesJson(): void
    {
        $payload = [
            ['cid' => '123.45a'],
        ];

        $this->client
            ->expects($this->once())
            ->method('get')
            ->with(
                'v1/templates',
                $this->callback(function (array $opts): bool {
                    $q = $opts['query'] ?? [];
                    $headers = $opts['headers'] ?? [];
                    return ($q['search-text'] ?? null) === 'vital' && ($q['restrict-search-to-main-data'] ?? null) === 'true'
                        && ($headers['Accept'] ?? null) === 'application/json';
                })
            )
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->templateSearch('vital');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(1, $result['items']);
        $this->assertArrayHasKey('cid', $result['items'][0]);
        $this->assertSame($payload[0]['cid'], $result['items'][0]['cid']);
    }

    public function testTemplateSearchFetchesMoreThenSlicesToMaxResults(): void
    {
        $maxResults = 8;
        $fetchSize = (int) ceil($maxResults * 1.5); // 12

        $payload = array_fill(0, $fetchSize, ['cid' => '1.0.0', 'resourceMainDisplayName' => 'Vital', 'projectName' => 'Test']);

        $capturedQuery = null;
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with(
                'v1/templates',
                $this->callback(function (array $opts) use (&$capturedQuery): bool {
                    $capturedQuery = $opts['query'] ?? [];
                    return true;
                })
            )
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->templateSearch('vital', $maxResults);

        $this->assertSame($fetchSize, (int) ($capturedQuery['size'] ?? 0), 'Request size should be 1.5× maxResults for ranking');
        $this->assertSame(0, (int) ($capturedQuery['offset'] ?? -1), 'Request offset should always be 0');
        $this->assertCount($maxResults, $result['items'], 'Returned items should be sliced to maxResults');
    }

    public function testTemplateGetRespectsFormatAndReturnsTextContent(): void
    {
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with(
                'v1/templates/my_template/opt',
                $this->callback(function (array $opts): bool {
                    return ($opts['headers']['Accept'] ?? null) === 'application/xml';
                })
            )
            ->willReturn(new Response(200, ['Content-Type' => 'application/xml'], '<opt>content</opt>'));

        $svc = new CkmService($this->client, $this->logger);
        $content = $svc->templateGet('my_template', 'opt');
        $this->assertInstanceOf(TextContent::class, $content);
        $this->assertStringContainsString('<opt>content</opt>', $content->text);
        $this->assertStringContainsString('```', $content->text);
    }
}
