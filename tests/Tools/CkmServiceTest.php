<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Tools\CkmService;
use GuzzleHttp\Psr7\Response;
use Mcp\Schema\Content\TextContent;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\NullLogger;

// The CkmClient double is used purely as a stub (return values, no call-count expectations) in the
// scoring/search tests; opt out of PHPUnit's mock-without-expectations notice for the whole class.
#[CoversClass(CkmService::class)]
#[AllowMockObjectsWithoutExpectations]
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
        // Updated for the wider re-ranking window: multiplier 3.0, capped at FETCH_SIZE_LIMIT (60).
        $fetchSize = min(60, (int) ceil($maxResults * 3.0)); // 30
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

        $this->assertSame($fetchSize, (int) ($capturedQuery['size'] ?? 0), 'Request size should be 3.0× maxResults (capped) for ranking');
        $this->assertSame(0, (int) ($capturedQuery['offset'] ?? -1), 'Request offset should always be 0');
        $this->assertCount($maxResults, $result['items'], 'Returned items should be sliced to maxResults');
    }

    public function testArchetypeSearchAllKeywordsMatchedScoresHigherThanPartialMatch(): void
    {
        $payload = [
            ['cid' => '1', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.a.v1', 'resourceMainDisplayName' => 'Blood pressure', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.b.v1', 'resourceMainDisplayName' => 'Blood glucose', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->archetypeSearch('blood pressure', 10);

        $this->assertCount(2, $result['items']);
        $first = $result['items'][0];
        $second = $result['items'][1];
        $this->assertGreaterThan($second['score'], $first['score'], 'Item matching all keywords (blood + pressure) should score higher than item matching one keyword');
        $this->assertStringContainsString('pressure', $first['name'] ?? '');
    }

    public function testArchetypeSearchWordBoundaryScoresHigherThanSubstring(): void
    {
        $payload = [
            ['cid' => '1', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.other.v1', 'resourceMainDisplayName' => 'Blood pressure', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.glucose.v1', 'resourceMainDisplayName' => 'Bloodstream infection', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->archetypeSearch('blood', 10);

        $this->assertCount(2, $result['items']);
        $first = $result['items'][0];
        $this->assertStringContainsString('Blood pressure', $first['name'] ?? '', 'Word-boundary match (Blood in "Blood pressure") should rank above substring match (blood in "Bloodstream infection")');
        $this->assertGreaterThan($result['items'][1]['score'], $first['score']);
    }

    public function testArchetypeSearchStatusAndProjectBonusApplied(): void
    {
        $payload = [
            ['cid' => '1', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.foo.v1', 'resourceMainDisplayName' => 'Vital signs', 'projectName' => 'Common resources', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.bar.v1', 'resourceMainDisplayName' => 'Vital signs', 'projectName' => 'Other', 'status' => 'DRAFT'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->archetypeSearch('vital', 10);

        $this->assertCount(2, $result['items']);
        $first = $result['items'][0];
        $this->assertSame('PUBLISHED', $first['status']);
        $this->assertGreaterThan($result['items'][1]['score'], $first['score'], 'PUBLISHED + common resources should score higher than DRAFT + other project');
    }

    public function testArchetypeSearchOlderDraftScoresLowerThanNewerDraft(): void
    {
        $twoYearsAgo = (new \DateTimeImmutable('now'))->modify('-2 years')->format(\DateTimeInterface::ATOM);
        $recent = (new \DateTimeImmutable('now'))->modify('-1 month')->format(\DateTimeInterface::ATOM);
        $payload = [
            ['cid' => '1', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.foo.v1', 'resourceMainDisplayName' => 'Foo', 'projectName' => 'Test', 'status' => 'DRAFT', 'creationTime' => $twoYearsAgo, 'modificationTime' => $twoYearsAgo],
            ['cid' => '2', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.bar.v1', 'resourceMainDisplayName' => 'Foo', 'projectName' => 'Test', 'status' => 'DRAFT', 'creationTime' => $recent, 'modificationTime' => $recent],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->archetypeSearch('foo', 10);

        $this->assertCount(2, $result['items']);
        $newer = $result['items'][0];
        $older = $result['items'][1];
        $this->assertGreaterThan($older['score'], $newer['score'], 'Newer DRAFT should score higher than older DRAFT (age penalty)');
    }

    public function testArchetypeSearchCapsFetchSizeAtFetchSizeLimit(): void
    {
        // maxResults=50 → 50*3.0 = 150, capped at FETCH_SIZE_LIMIT (60). The fetch window is now
        // decoupled from MAX_RESULTS_LIMIT (50), which still caps the *returned* count.
        $maxResults = 50; // MAX_RESULTS_LIMIT

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

        $this->assertSame(60, (int) ($capturedQuery['size'] ?? 0));
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

    public function testTemplateSearchAllKeywordsMatchedScoresHigherThanPartialMatch(): void
    {
        $payload = [
            ['cid' => '1', 'resourceMainDisplayName' => 'Vital signs summary', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainDisplayName' => 'Vital only', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->templateSearch('vital signs', 10);

        $this->assertCount(2, $result['items']);
        $this->assertGreaterThan($result['items'][1]['score'], $result['items'][0]['score'], 'Template matching all keywords should score higher');
        $this->assertStringContainsString('summary', $result['items'][0]['name'] ?? '');
    }

    public function testTemplateSearchWordBoundaryScoresHigherThanSubstring(): void
    {
        $payload = [
            ['cid' => '1', 'resourceMainDisplayName' => 'Vital signs', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainDisplayName' => 'Vitalscreening', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->templateSearch('vital', 10);

        $this->assertCount(2, $result['items']);
        $this->assertGreaterThan($result['items'][1]['score'], $result['items'][0]['score'], 'Word-boundary match (Vital in "Vital signs") should score higher than substring (vital in Vitalscreening)');
    }

    public function testTemplateSearchStatusAndProjectBonusApplied(): void
    {
        $payload = [
            ['cid' => '1', 'resourceMainDisplayName' => 'Discharge', 'projectName' => 'Common resources', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainDisplayName' => 'Discharge', 'projectName' => 'Other', 'status' => 'DRAFT'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->templateSearch('discharge', 10);

        $this->assertCount(2, $result['items']);
        $this->assertSame('PUBLISHED', $result['items'][0]['status']);
        $this->assertGreaterThan($result['items'][1]['score'], $result['items'][0]['score'], 'PUBLISHED + common resources should score higher than DRAFT');
    }

    public function testTemplateSearchOlderDraftScoresLowerThanNewerDraft(): void
    {
        $twoYearsAgo = (new \DateTimeImmutable('now'))->modify('-2 years')->format(\DateTimeInterface::ATOM);
        $recent = (new \DateTimeImmutable('now'))->modify('-1 month')->format(\DateTimeInterface::ATOM);
        $payload = [
            ['cid' => '1', 'resourceMainDisplayName' => 'Discharge', 'projectName' => 'Test', 'status' => 'DRAFT', 'creationTime' => $twoYearsAgo, 'modificationTime' => $twoYearsAgo],
            ['cid' => '2', 'resourceMainDisplayName' => 'Discharge', 'projectName' => 'Test', 'status' => 'DRAFT', 'creationTime' => $recent, 'modificationTime' => $recent],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->templateSearch('discharge', 10);

        $this->assertCount(2, $result['items']);
        $this->assertGreaterThan($result['items'][1]['score'], $result['items'][0]['score'], 'Newer DRAFT template should score higher than older DRAFT (age penalty)');
    }

    public function testTemplateSearchFetchesMoreThenSlicesToMaxResults(): void
    {
        $maxResults = 8;
        // Updated for the wider re-ranking window: multiplier 3.0, capped at FETCH_SIZE_LIMIT (60).
        $fetchSize = min(60, (int) ceil($maxResults * 3.0)); // 24

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

        $this->assertSame($fetchSize, (int) ($capturedQuery['size'] ?? 0), 'Request size should be 3.0× maxResults (capped) for ranking');
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

    public function testArchetypeSearchRmClassFilterKeepsMatchingDropsOthers(): void
    {
        $payload = [
            ['cid' => '1', 'resourceMainId' => 'openEHR-EHR-COMPOSITION.health_summary.v1', 'resourceMainDisplayName' => 'Health summary', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.body_weight.v1', 'resourceMainDisplayName' => 'Body weight', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '3', 'resourceMainId' => 'openEHR-EHR-COMPOSITION.encounter.v1', 'resourceMainDisplayName' => 'Encounter', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        // case-insensitive filter on the RM-class segment
        $result = $svc->archetypeSearch('summary', 10, true, 'composition');

        $this->assertCount(2, $result['items'], 'Only COMPOSITION archetypes should remain after the rm_class filter');
        $ids = array_map(static fn(array $i): string => $i['archetypeId'], $result['items']);
        $this->assertContains('openEHR-EHR-COMPOSITION.health_summary.v1', $ids);
        $this->assertContains('openEHR-EHR-COMPOSITION.encounter.v1', $ids);
        $this->assertNotContains('openEHR-EHR-OBSERVATION.body_weight.v1', $ids, 'OBSERVATION item must be filtered out');
    }

    public function testArchetypeSearchRejectsMalformedRmClass(): void
    {
        $svc = new CkmService($this->client, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        // digits/punctuation are not a valid RM class token
        $svc->archetypeSearch('summary', 10, true, 'comp-osition!');
    }

    public function testArchetypeSearchExactConceptBoostRanksConceptMatchFirst(): void
    {
        // All items share status/project, and the decoys also match both keywords (so they get the
        // all-keywords bonus). Only the exact-concept item — whose concept equals the full query —
        // earns the extra exact-concept bonus, which must lift it to the top. It is listed last in
        // CKM's order to prove re-ranking surfaces it.
        $payload = [
            ['cid' => '1', 'resourceMainId' => 'openEHR-EHR-COMPOSITION.health_summary_report.v1', 'resourceMainDisplayName' => 'Health summary report', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainId' => 'openEHR-EHR-COMPOSITION.detailed_health_summary.v1', 'resourceMainDisplayName' => 'Detailed health summary', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '3', 'resourceMainId' => 'openEHR-EHR-COMPOSITION.health_summary.v1', 'resourceMainDisplayName' => 'Health summary', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->archetypeSearch('health summary', 10);

        $this->assertCount(3, $result['items']);
        $first = $result['items'][0];
        $this->assertSame('openEHR-EHR-COMPOSITION.health_summary.v1', $first['archetypeId'], 'Exact concept match (concept == query) should rank first');
        $this->assertGreaterThan($result['items'][1]['score'], $first['score']);
    }

    public function testArchetypeSearchExactConceptBoostMatchesConceptFromArchetypeId(): void
    {
        // The display name does not contain the query at all; only the concept-from-id matches.
        $payload = [
            ['cid' => '1', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.blood_pressure.v1', 'resourceMainDisplayName' => 'BP measurement', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.body_weight.v1', 'resourceMainDisplayName' => 'Blood pressure related weight', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->archetypeSearch('blood pressure', 10);

        $this->assertCount(2, $result['items']);
        $this->assertSame('openEHR-EHR-OBSERVATION.blood_pressure.v1', $result['items'][0]['archetypeId'], 'Concept derived from archetype-id (blood_pressure → "blood pressure") should trigger the exact-concept boost');
    }

    public function testTemplateSearchSoapAliasScoresSoepNamedItem(): void
    {
        // Scoring alias only (SOAP↔SOEP): a "SOAP" query treats a "SOEP"-named template as an
        // exact-concept match (+bonus). The decoy does not contain "soap"/"soep" at all, so only
        // the aliased exact-concept match gives the SOEP item a higher score and the top rank.
        $payload = [
            ['cid' => '1', 'resourceMainDisplayName' => 'SOEP', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
            ['cid' => '2', 'resourceMainDisplayName' => 'Discharge summary', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $this->client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $svc = new CkmService($this->client, $this->logger);
        $result = $svc->templateSearch('SOAP', 10);

        $this->assertCount(2, $result['items']);
        $this->assertSame('SOEP', $result['items'][0]['name'], 'SOAP↔SOEP scoring alias should make the SOEP-named item an exact-concept match and rank it first');
        // The SOEP item earns the exact-concept bonus purely via the alias; the decoy earns no keyword score.
        $this->assertGreaterThan($result['items'][1]['score'], $result['items'][0]['score']);
    }
}
