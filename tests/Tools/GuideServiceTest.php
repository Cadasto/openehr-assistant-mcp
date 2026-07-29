<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService;
use Mcp\Exception\ToolCallException;
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
        $default = $this->service->search('cardinality occurrences constraints', 'archetypes', null, 5, 220, 20);
        $expandedPool = $this->service->search('cardinality occurrences constraints', 'archetypes', null, 5, 220, 40);

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

        $expanded = $this->service->search('aql', null, null, 12, 500, 30);
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

    public function test_guideGet_rejects_path_traversal_segments(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid guide category');

        $this->service->get('', '../secrets', 'anything');
    }

    public function test_guideGet_rejects_path_traversal_in_uri(): void
    {
        $this->expectException(ToolCallException::class);

        $this->service->get('openehr://guides/../secrets/anything');
    }

    public function test_guideSearch_is_case_insensitive_for_query_tokens(): void
    {
        $lower = $this->service->search('aql');
        $upper = $this->service->search('AQL');

        $this->assertNotEmpty($lower['items']);
        $this->assertNotEmpty($upper['items']);
        $lowerUris = array_map(static fn(array $i): string => (string)$i['resourceUri'], $lower['items']);
        $upperUris = array_map(static fn(array $i): string => (string)$i['resourceUri'], $upper['items']);
        $this->assertSame($lowerUris, $upperUris);
        foreach ($upper['items'] as $item) {
            $this->assertGreaterThan(0, $item['score']);
        }
    }

    public function test_guideSearch_filters_zero_score_when_query_non_empty(): void
    {
        $results = $this->service->search('zzzxnonexistenttoken123');
        $this->assertSame([], $results['items']);
    }

    public function test_guideScoring_awards_title_and_category_bonus_once_per_guide(): void
    {
        // The title and category bonuses belong to the *guide*; occurrence counting belongs
        // to a *text*. `search()` scores metadata and body as two texts, so bonuses living in
        // the per-text scorer would be earned twice — silently doubling the weight of a title
        // hit against body relevance. Both scorers are internal to ranking, so the split is
        // pinned here rather than through the envelope.
        $scoreGuide = new \ReflectionMethod(GuideService::class, 'scoreGuide');
        $scoreOccurrences = new \ReflectionMethod(GuideService::class, 'scoreOccurrences');
        $body = 'cardinality appears twice here: cardinality.';

        $this->assertSame(2, $scoreOccurrences->invoke($this->service, 'cardinality', $body));
        $this->assertSame(0, $scoreOccurrences->invoke($this->service, 'cardinality', 'unrelated text'));

        // 2 occurrences + one title bonus (4) + one category bonus (3).
        $this->assertSame(
            9,
            $scoreGuide->invoke($this->service, 'cardinality', 'Cardinality', $body, 'cardinality'),
        );
    }

    public function test_guideSearch_taskType_is_boost_not_hard_filter(): void
    {
        // A taskType string unlikely to appear verbatim in every matching guide body
        // must not wipe otherwise relevant hits.
        $plain = $this->service->search('aql');
        $boosted = $this->service->search('aql', null, 'definitely-not-in-guide-bodies-xyz');
        $this->assertNotEmpty($plain['items']);
        $this->assertNotEmpty($boosted['items']);
    }

    public function test_guideSearch_taskType_actually_boosts_a_matching_guide(): void
    {
        // The other half of the contract: without this, making taskTypeBoost() return 0
        // — a complete no-op — would keep the suite green.
        $plain = $this->service->search('archetype', maxResults: 50);
        $boosted = $this->service->search('archetype', null, 'checklist', maxResults: 50);

        $scoreOf = static function (array $result, string $name): int {
            foreach ($result['items'] as $item) {
                if ($item['name'] === $name) {
                    return (int) $item['score'];
                }
            }

            return -1;
        };
        $rankOf = static function (array $result, string $name): int {
            foreach (array_values($result['items']) as $index => $item) {
                if ($item['name'] === $name) {
                    return $index;
                }
            }

            return PHP_INT_MAX;
        };

        $this->assertGreaterThan($scoreOf($plain, 'checklist'), $scoreOf($boosted, 'checklist'));
        $this->assertLessThan($rankOf($plain, 'checklist'), $rankOf($boosted, 'checklist'));
    }

    public function test_guideSearch_taskType_cannot_resurrect_a_guide_the_query_did_not_match(): void
    {
        // Regression: the boost used to be folded into the score the relevance floor tests,
        // so `taskType: review` alone returned nine guides for a nonsense query.
        $withHint = $this->service->search('zzzxnonexistenttoken123', null, 'review');

        $this->assertSame([], $withHint['items']);
        $this->assertSame(0, $withHint['total']);
    }

    public function test_guideSearch_keeps_openehr_identifiers_as_single_tokens(): void
    {
        // Splitting on `-` would reduce this to openehr/ehr/observation and match the whole
        // corpus on its most generic parts, defeating the relevance floor entirely.
        $identifier = $this->service->search('openEHR-EHR-OBSERVATION', maxResults: 50, topCandidates: 200);
        $everything = $this->service->search('archetype', maxResults: 50, topCandidates: 200);

        $this->assertNotEmpty($identifier['items']);
        $this->assertLessThan($everything['total'], $identifier['total']);
    }

    public function test_guideSearch_finds_a_term_that_appears_only_in_a_guide_body(): void
    {
        // Regression: the candidate window was ranked on metadata (title/abstract/headings)
        // alone and sliced to 24 of 66 guides *before* any body was scored. A term living
        // solely in a body therefore scored 0 everywhere, and the zero-score floor turned
        // that into an empty envelope — which an agent reads as "no such guidance exists".
        // `access_token` occurs only in the body of specs/its-rest-smart_app_launch, which
        // sorted 26th by name and so fell outside the old window. Asserted at *default*
        // parameters: passing `topCandidates: 200` is the workaround real clients cannot apply.
        $result = $this->service->search('access_token');

        $names = array_map(static fn(array $i): string => (string) $i['name'], $result['items']);
        $this->assertContains('its-rest-smart_app_launch', $names);
        $this->assertGreaterThan(0, $result['total']);
    }

    public function test_guideSearch_body_only_term_reports_every_matching_guide(): void
    {
        // Same defect, multi-hit shape: `hide_on_form` occurs in four template guides, three
        // of which sorted outside the old window, so the tool reported a single hit.
        $result = $this->service->search('hide_on_form', maxResults: 50);

        $this->assertGreaterThanOrEqual(4, $result['total']);
    }

    public function test_guideSearch_taskType_cannot_evict_a_genuine_match(): void
    {
        // The other half of the documented "never filters" contract. It held for the
        // relevance floor but not for the old candidate window, where a +2 boost on
        // unrelated guides could push a genuine body-only match out of scoring entirely,
        // so adding a hint *removed* a result.
        $without = $this->service->search('access_token');
        $withHint = $this->service->search('access_token', null, 'review');

        $names = static fn(array $r): array => array_map(static fn(array $i): string => (string) $i['name'], $r['items']);
        $this->assertSame($names($without), $names($withHint));
        $this->assertSame($without['total'], $withHint['total']);
    }

    public function test_guideSearch_splits_on_punctuation_between_terms(): void
    {
        $comma = $this->service->search('cardinality, occurrences', maxResults: 5);
        $slash = $this->service->search('cardinality/occurrences', maxResults: 5);
        $space = $this->service->search('cardinality occurrences', maxResults: 5);

        $names = static fn(array $result): array => array_map(static fn(array $i): string => (string) $i['name'], $result['items']);
        $this->assertSame($names($space), $names($comma));
        $this->assertSame($names($space), $names($slash));
    }

    public function test_guideSearch_matches_non_ascii_query_terms_case_insensitively(): void
    {
        // mb_strtolower vs strtolower: the latter leaves non-ASCII uppercase untouched, so a
        // capitalised accented term would score 0 everywhere. `bokmål` occurs in the Norwegian
        // language-standards guide, so both spellings must find it — asserted non-empty so the
        // comparison cannot pass vacuously.
        $lower = $this->service->search('bokmål', maxResults: 50, topCandidates: 200);
        $upper = $this->service->search('BOKMÅL', maxResults: 50, topCandidates: 200);

        $this->assertNotEmpty($lower['items'], 'expected the corpus to contain the non-ASCII term');
        $this->assertGreaterThan(0, $lower['total']);
        $this->assertSame($lower['total'], $upper['total']);
        $names = static fn(array $result): array => array_map(static fn(array $i): string => (string) $i['name'], $result['items']);
        $this->assertSame($names($lower), $names($upper));
    }

    public function test_guideSearch_with_an_empty_query_returns_the_whole_candidate_window(): void
    {
        // The zero-score filter is guarded by `$query !== ''`; dropping that guard would make
        // the documented "leave empty to search all guides" behaviour return nothing.
        $results = $this->service->search('');

        $this->assertNotEmpty($results['items']);
        $this->assertGreaterThan(0, $results['total']);
    }

    public function test_guideSearch_snippets_are_valid_utf8_and_json_encodable(): void
    {
        // Regression: byte-based snippet slicing cut multibyte sequences mid-character, and
        // json_encode then failed for the entire JSON-RPC envelope — the client received no
        // response at all rather than a truncated snippet.
        foreach (['occurrences', 'terminology', 'språk', 'ordinal'] as $query) {
            $results = $this->service->search($query, maxResults: 50, topCandidates: 200);
            foreach ($results['items'] as $item) {
                $snippet = (string) $item['snippet'];
                $this->assertTrue(mb_check_encoding($snippet, 'UTF-8'), sprintf('snippet for "%s" is not valid UTF-8', $query));
            }
            $this->assertIsString(json_encode($results, JSON_THROW_ON_ERROR));
        }
    }

    public function test_adlIdiomLookup_snippets_are_valid_utf8_and_json_encodable(): void
    {
        foreach (['coded text', 'scale', 'occurrences', 'slots'] as $pattern) {
            $results = $this->service->adlIdiomLookup($pattern);
            foreach ($results['items'] as $item) {
                $this->assertTrue(mb_check_encoding((string) $item['snippet'], 'UTF-8'));
            }
            $this->assertIsString(json_encode($results, JSON_THROW_ON_ERROR));
        }
    }
}
