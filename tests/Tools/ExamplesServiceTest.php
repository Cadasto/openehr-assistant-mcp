<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Tools\ExamplesService;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\TextResourceContents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(ExamplesService::class)]
final class ExamplesServiceTest extends TestCase
{
    private ExamplesService $service;

    protected function setUp(): void
    {
        $this->service = new ExamplesService(new NullLogger());
    }

    public function test_search_returns_matches_for_blood_pressure(): void
    {
        $results = $this->service->search('blood pressure');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('items', $results);
        $this->assertNotEmpty($results['items']);

        $first = $results['items'][0];
        $this->assertArrayHasKey('resourceUri', $first);
        $this->assertStringStartsWith('openehr://examples/', $first['resourceUri']);
        $this->assertLessThanOrEqual(10, count($results['items']));
    }

    public function test_search_respects_kind_filter(): void
    {
        $results = $this->service->search('', 'aql');

        $this->assertNotEmpty($results['items']);
        foreach ($results['items'] as $item) {
            $this->assertSame('aql', $item['kind']);
        }
    }

    public function test_search_empty_query_lists_everything_in_kind(): void
    {
        $aql = $this->service->search('', 'aql');
        $flat = $this->service->search('', 'flat');
        $structured = $this->service->search('', 'structured');

        $this->assertNotEmpty($aql['items']);
        $this->assertNotEmpty($flat['items']);
        $this->assertNotEmpty($structured['items']);
    }

    public function test_get_by_uri(): void
    {
        $uri = 'openehr://examples/aql/latest_blood_pressure_per_ehr';
        $payload = $this->service->get($uri);

        $this->assertInstanceOf(EmbeddedResource::class, $payload);
        $this->assertInstanceOf(TextResourceContents::class, $payload->resource);
        $this->assertSame($uri, $payload->resource->uri);
        $this->assertSame('text/markdown', $payload->resource->mimeType);
        $this->assertStringContainsString('```aql', $payload->resource->text);
    }

    public function test_get_by_kind_and_name(): void
    {
        $payload = $this->service->get('', 'flat', 'vital_signs_blood_pressure');

        $this->assertInstanceOf(EmbeddedResource::class, $payload);
        $this->assertSame('openehr://examples/flat/vital_signs_blood_pressure', $payload->resource->uri);
        $this->assertStringContainsString('openehr.wt.flat+json', $payload->resource->text);
    }

    public function test_get_unknown_throws(): void
    {
        $this->expectException(\Mcp\Exception\ToolCallException::class);
        $this->service->get('', 'aql', 'this_example_does_not_exist');
    }

    public function test_paired_flat_and_structured_exist_for_blood_pressure(): void
    {
        $flat = $this->service->get('', 'flat', 'vital_signs_blood_pressure');
        $structured = $this->service->get('', 'structured', 'vital_signs_blood_pressure');

        $this->assertStringContainsString('wt.flat+json', $flat->resource->text);
        $this->assertStringContainsString('wt.structured+json', $structured->resource->text);
    }

    public function test_get_archetype_returns_native_adl_with_text_plain_mime(): void
    {
        $payload = $this->service->get('', 'archetypes', 'openEHR-EHR-OBSERVATION.blood_pressure.v2');

        $this->assertInstanceOf(EmbeddedResource::class, $payload);
        $this->assertSame('openehr://examples/archetypes/openEHR-EHR-OBSERVATION.blood_pressure.v2', $payload->resource->uri);
        $this->assertSame('text/plain', $payload->resource->mimeType);
        $this->assertStringContainsString('archetype', $payload->resource->text);
        $this->assertStringContainsString('openEHR-EHR-OBSERVATION.blood_pressure.v2', $payload->resource->text);
    }

    public function test_search_kind_archetypes_lists_curated_set(): void
    {
        $results = $this->service->search('', 'archetypes');

        $this->assertNotEmpty($results['items']);
        foreach ($results['items'] as $item) {
            $this->assertSame('archetypes', $item['kind']);
            $this->assertStringStartsWith('openEHR-EHR-', $item['name']);
        }
    }

    public function test_search_splits_on_punctuation_like_guide_search_does(): void
    {
        // `examples_search` kept whitespace-only tokenization after `guide_search` was fixed,
        // so `pressure,` matched nothing and the two tools ranked the same query differently.
        // Both now share Helpers\SearchTokenizer.
        $names = static fn (array $r): array => array_map(static fn (array $i): string => (string) $i['name'], $r['items']);

        $comma = $this->service->search('blood pressure, weight');
        $space = $this->service->search('blood pressure weight');

        $this->assertNotEmpty($comma['items'], 'expected the corpus to match this query');
        $this->assertSame($names($space), $names($comma));
    }

    public function test_search_folds_non_ascii_case(): void
    {
        // Byte-wise strtolower left non-ASCII uppercase unfolded, so a capitalised accented
        // term scored 0 across the whole corpus.
        $lower = $this->service->search('blood pressure');
        $upper = $this->service->search('BLOOD PRESSURE');

        $this->assertNotEmpty($lower['items']);
        $this->assertSame($lower['total'], $upper['total']);
    }
}
