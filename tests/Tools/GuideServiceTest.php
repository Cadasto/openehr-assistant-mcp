<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\TextResourceContents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(GuideService::class)]
final class GuideServiceTest extends TestCase
{
    private GuideService $service;

    protected function setUp(): void
    {
        $this->service = new GuideService(new NullLogger());
    }

    public function test_guideSearch_returns_matches(): void
    {
        $results = $this->service->search('cardinality');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('items', $results);
        $this->assertNotEmpty($results['items']);
        $first = $results['items'][0];
        $this->assertArrayHasKey('resourceUri', $first);
        $this->assertStringStartsWith('openehr://guides/', $first['resourceUri']);
        $this->assertLessThanOrEqual(10, count($results['items']));
    }

    public function test_guideSearch_ranking_consistent_across_candidate_pool_sizes(): void
    {
        $default = $this->service->search('cardinality occurrences constraints', 'archetypes', '', 5, 220, 20);
        $expandedPool = $this->service->search('cardinality occurrences constraints', 'archetypes', '', 5, 220, 40);

        $defaultUris = array_map(static fn(array $item): string => (string)$item['resourceUri'], $default['items']);
        $expandedUris = array_map(static fn(array $item): string => (string)$item['resourceUri'], $expandedPool['items']);

        $this->assertNotEmpty($defaultUris);
        $this->assertSame($defaultUris, $expandedUris);
    }

    public function test_guideSearch_respects_response_size_defaults_and_overrides(): void
    {
        $default = $this->service->search('aql');
        $this->assertLessThanOrEqual(10, count($default['items']));
        foreach ($default['items'] as $item) {
            $this->assertLessThanOrEqual(220, mb_strlen((string)$item['snippet']));
        }

        $expanded = $this->service->search('aql', '', '', 12, 500, 30);
        $this->assertLessThanOrEqual(12, count($expanded['items']));
        $this->assertNotEmpty($expanded['items']);
        $this->assertLessThanOrEqual(500, mb_strlen((string)$expanded['items'][0]['snippet']));
    }

    public function test_guideSearch_respects_category_filter(): void
    {
        $results = $this->service->search('template', 'templates');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('items', $results);
        foreach ($results['items'] as $item) {
            $this->assertSame('templates', $item['category']);
        }
    }

    public function test_guideGet_by_uri(): void
    {
        $resourceUri = 'openehr://guides/archetypes/adl-idioms-cheatsheet';
        $payload = $this->service->get($resourceUri);

        $this->assertInstanceOf(EmbeddedResource::class, $payload);
        $this->assertSame('resource', $payload->type);
        $this->assertInstanceOf(TextResourceContents::class, $payload->resource);
        $this->assertSame($resourceUri, $payload->resource->uri);
        $this->assertSame('text/markdown', $payload->resource->mimeType);
        $this->assertNotEmpty($payload->resource->text);
        $this->assertStringContainsString('idioms', $payload->resource->text);
    }

    public function test_guideGet_by_title(): void
    {
        $payload = $this->service->get('', 'archetypes', 'adl-idioms-cheatsheet');

        $this->assertInstanceOf(EmbeddedResource::class, $payload);
        $this->assertSame('resource', $payload->type);
        $this->assertInstanceOf(TextResourceContents::class, $payload->resource);
        $this->assertSame('openehr://guides/archetypes/adl-idioms-cheatsheet', $payload->resource->uri);
        $this->assertSame('text/markdown', $payload->resource->mimeType);
        $this->assertNotEmpty($payload->resource->text);
        $this->assertStringContainsString('idioms', $payload->resource->text);
    }

    public function test_adlIdiomLookup_returns_matches(): void
    {
        $results = $this->service->adlIdiomLookup('cardinality');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('items', $results);
        $this->assertNotEmpty($results['items']);
        $this->assertArrayHasKey('resourceUri', $results['items'][0]);
    }
}
