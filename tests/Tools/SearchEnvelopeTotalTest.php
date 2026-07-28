<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Tools\CkmService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\ExamplesService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Pins the meaning of the `total` companion on every search envelope.
 *
 * `total` exists so a client can tell whether widening the request would surface more
 * results. Counting *after* the cap instead — the easy mistake — makes it a duplicate of
 * `items.length` and silently useless, which no schema check would catch.
 */
#[CoversNothing]
#[AllowMockObjectsWithoutExpectations]
final class SearchEnvelopeTotalTest extends TestCase
{
    public function test_guide_search_total_counts_matches_beyond_the_returned_items(): void
    {
        $service = new GuideService(new NullLogger());
        $result = $service->search('archetype', maxResults: 2);

        $this->assertCount(2, $result['items']);
        $this->assertGreaterThan(2, $result['total']);
    }

    public function test_guide_search_total_is_bounded_by_top_candidates_as_documented(): void
    {
        // `total` is scoped to the scoring window, and the schema says so. Raising
        // `topCandidates` must widen both the search and the count.
        $service = new GuideService(new NullLogger());

        $narrow = $service->search('archetype', maxResults: 1, topCandidates: 5);
        $wide = $service->search('archetype', maxResults: 1, topCandidates: 200);

        $this->assertLessThanOrEqual(5, $narrow['total']);
        $this->assertGreaterThan($narrow['total'], $wide['total']);
    }

    public function test_examples_search_total_counts_matches_beyond_the_returned_items(): void
    {
        $service = new ExamplesService(new NullLogger());
        $result = $service->search('openehr', maxResults: 1);

        $this->assertCount(1, $result['items']);
        $this->assertGreaterThan(1, $result['total']);
    }

    public function test_adl_idiom_lookup_total_counts_matches_beyond_the_section_cap(): void
    {
        $service = new GuideService(new NullLogger());
        // A single letter matches nearly every section, so the 7-section cap engages.
        $result = $service->adlIdiomLookup('a');

        $this->assertNotEmpty($result['items']);
        $this->assertLessThanOrEqual(7, count($result['items']));
        $this->assertGreaterThan(count($result['items']), $result['total']);
    }

    public function test_type_specification_search_total_equals_the_item_count(): void
    {
        // The inverse invariant: this tool documents that it neither caps nor paginates,
        // so anyone "fixing consistency" by adding a cap breaks the published contract.
        $service = new TypeSpecificationService(new NullLogger());
        $result = $service->search('composition');

        $this->assertNotEmpty($result['items']);
        $this->assertSame(count($result['items']), $result['total']);
    }

    public function test_type_specification_search_reports_zero_for_a_rejected_pattern(): void
    {
        $service = new TypeSpecificationService(new NullLogger());
        $result = $service->search('ab');

        $this->assertSame([], $result['items']);
        $this->assertSame(0, $result['total']);
    }

    public function test_ckm_search_total_uses_the_upstream_header_when_present(): void
    {
        $service = $this->ckmServiceReturning(
            [['cid' => '1.1', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.bp.v1', 'resourceMainDisplayName' => 'BP', 'status' => 'PUBLISHED']],
            ['X-Total-Count' => '482'],
        );

        $result = $service->archetypeSearch('blood');

        $this->assertCount(1, $result['items']);
        $this->assertSame(482, $result['total']);
    }

    public function test_ckm_search_total_never_undercounts_the_returned_items(): void
    {
        // Regression: a non-numeric header used to yield `total: 0` alongside a non-empty
        // `items` list, contradicting the field's own contract.
        $service = $this->ckmServiceReturning(
            [['cid' => '1.1', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.bp.v1', 'resourceMainDisplayName' => 'BP', 'status' => 'PUBLISHED']],
            ['X-Total-Count' => 'not-a-number'],
        );

        $result = $service->archetypeSearch('blood');

        $this->assertCount(1, $result['items']);
        $this->assertSame(1, $result['total']);
    }

    public function test_ckm_search_total_reflects_the_local_rm_class_filter(): void
    {
        // The upstream count covers the keyword search only; `rmClass` is applied here, so
        // reporting the header would tell the client there are hundreds of OBSERVATIONs
        // when the filter matched one.
        $service = $this->ckmServiceReturning(
            [
                ['cid' => '1.1', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.bp.v1', 'resourceMainDisplayName' => 'BP', 'status' => 'PUBLISHED'],
                ['cid' => '2.2', 'resourceMainId' => 'openEHR-EHR-CLUSTER.location.v1', 'resourceMainDisplayName' => 'Location', 'status' => 'PUBLISHED'],
                ['cid' => '3.3', 'resourceMainId' => 'openEHR-EHR-EVALUATION.problem.v1', 'resourceMainDisplayName' => 'Problem', 'status' => 'PUBLISHED'],
            ],
            ['X-Total-Count' => '482'],
        );

        $result = $service->archetypeSearch('blood', rmClass: 'OBSERVATION');

        $this->assertCount(1, $result['items']);
        $this->assertSame(1, $result['total']);
    }

    /**
     * @param list<array<string, mixed>> $payload
     * @param array<string, string> $headers
     */
    private function ckmServiceReturning(array $payload, array $headers = []): CkmService
    {
        $client = $this->createMock(CkmClient::class);
        $client->method('get')->willReturn(new Response(
            200,
            ['Content-Type' => 'application/json'] + $headers,
            json_encode($payload, JSON_THROW_ON_ERROR),
        ));

        return new CkmService($client, new NullLogger());
    }
}
