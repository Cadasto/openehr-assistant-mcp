<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Tools\CkmService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Content\TextContent;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;

// The CkmClient double is used purely as a stub (return values, no call-count expectations) in the
// scoring/search tests; opt out of PHPUnit's mock-without-expectations notice for the whole class.
// The "OverGuzzle" tests instead run the real CkmClient over a Guzzle MockHandler.
#[CoversClass(CkmService::class)]
#[UsesClass(CkmClient::class)]
#[AllowMockObjectsWithoutExpectations]
final class CkmServiceTest extends TestCase
{
    /** Shaped like the production CKM_API_BASE_URL (trailing slash); never dialled. */
    private const string MOCK_CKM_BASE_URI = 'https://ckm.example.test/ckm/rest/';

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

    public function testArchetypeGetTurnsAnUnknownFormatIntoToolCallException(): void
    {
        $this->client->expects($this->never())->method('get');
        $svc = new CkmService($this->client, $this->logger);

        try {
            $svc->archetypeGet('123.45', 'docx');
            $this->fail('expected a ToolCallException for an unknown archetype format');
        } catch (ToolCallException $e) {
            $this->assertSame('Invalid archetype format: docx', $e->getMessage());
            $this->assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
        }
    }

    public function testTemplateGetTurnsAnUnknownFormatIntoToolCallException(): void
    {
        $this->client->expects($this->never())->method('get');
        $svc = new CkmService($this->client, $this->logger);

        try {
            $svc->templateGet('123.45', 'docx');
            $this->fail('expected a ToolCallException for an unknown template format');
        } catch (ToolCallException $e) {
            $this->assertSame('Invalid template format: docx', $e->getMessage());
            $this->assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
        }
    }

    public function testArchetypeGetRespectsFormatAndReturnsTextContent(): void
    {
        // format "adl" -> Map::archetypeFormat('adl') returns 'adl' and contentType 'text/plain' via Map::contentType
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->callback(function (string $endpoint): bool {
                    // A CID is passed through unchanged: v1/archetypes/{cid}/{format}
                    return str_starts_with($endpoint, 'v1/archetypes/123.45/') && str_ends_with($endpoint, '/adl');
                }),
                $this->callback(function (array $opts): bool {
                    return ($opts['headers']['Accept'] ?? null) === 'text/plain';
                })
            )
            ->willReturn(new Response(200, ['Content-Type' => 'text/plain'], 'archetype ADL content'));

        $svc = new CkmService($this->client, $this->logger);
        $content = $svc->archetypeGet('123.45', 'adl');
        $this->assertInstanceOf(TextContent::class, $content);
        $this->assertStringContainsString('archetype ADL content', $content->text);
        $this->assertStringContainsString('```', $content->text);
    }

    public function testArchetypeGetRejectsAnIdentifierItCannotResolve(): void
    {
        // Regression: a non-CID, non-archetype-id identifier used to be mangled by
        // `preg_replace('/[^\d.]/', '-', …)` and requested anyway, so the client was told
        // "Failed to retrieve the CKM Archetype: 404" — pointing at the archetype rather
        // than the identifier. No request should be issued at all.
        $this->client->expects($this->never())->method('get');

        $svc = new CkmService($this->client, $this->logger);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageMatches('/Could not resolve "123\.45a" to a CKM citeable identifier/');
        $svc->archetypeGet('123.45a', 'adl');
    }

    public function testArchetypeGetReportsAFailedCidResolutionRatherThanGuessing(): void
    {
        // A 200 with an empty body used to survive as '' and produce `v1/archetypes//adl`.
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with($this->stringContains('citeable-identifier'))
            ->willReturn(new Response(200, [], ''));

        $svc = new CkmService($this->client, $this->logger);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageMatches('/Could not resolve .* to a CKM citeable identifier/');
        $svc->archetypeGet('openEHR-EHR-OBSERVATION.blood_pressure.v1', 'adl');
    }

    public function testArchetypeGetResolvesAnArchetypeIdToACid(): void
    {
        $matcher = $this->exactly(2);
        $this->client
            ->expects($matcher)
            ->method('get')
            ->willReturnCallback(function (string $endpoint) use ($matcher): Response {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertStringContainsString('citeable-identifier/openEHR-EHR-OBSERVATION.blood_pressure.v1', $endpoint);

                    // Quoted, as a JSON string response would arrive.
                    return new Response(200, [], '"1249.32.1234"');
                }

                $this->assertSame('v1/archetypes/1249.32.1234/adl', $endpoint);

                return new Response(200, ['Content-Type' => 'text/plain'], 'resolved ADL');
            });

        $svc = new CkmService($this->client, $this->logger);
        $content = $svc->archetypeGet('openEHR-EHR-OBSERVATION.blood_pressure.v1', 'adl');

        $this->assertStringContainsString('resolved ADL', $content->text);
    }

    /**
     * Archetype-ids that are not exactly one path segment. Put into the resolution path as
     * given, each one made Guzzle resolve the request against `base_uri` to another page on
     * the CKM host, e.g. `openEHR-/../../../../admin?x=1` fetched `/ckm/rest/admin?x=1`.
     *
     * @return array<string, array{string}>
     */
    public static function archetypeIdsThatAreNotOnePathSegmentProvider(): array
    {
        return [
            'parent traversal and query' => ['openEHR-/../../../../admin?x=1'],
            'query' => ['openEHR-EHR-OBSERVATION.blood_pressure.v1?evil=1'],
            'fragment' => ['openEHR-EHR-OBSERVATION.blood_pressure.v1#top'],
            'slash' => ['openEHR-EHR-OBSERVATION/blood_pressure.v1'],
            'backslash' => ['openEHR-EHR-OBSERVATION.blood_pressure.v1\\..\\..'],
            'percent escape' => ['openEHR-%2e%2e%2fadmin'],
            'inner control character' => ["openEHR-EHR-OBSERVATION.blood\x01pressure.v1"],
        ];
    }

    #[DataProvider('archetypeIdsThatAreNotOnePathSegmentProvider')]
    public function testArchetypeGetRejectsAnArchetypeIdThatIsNotOnePathSegment(string $archetypeId): void
    {
        $this->client->expects($this->never())->method('get');
        $svc = new CkmService($this->client, $this->logger);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage(sprintf('Could not resolve "%s" to a CKM citeable identifier (CID).', $archetypeId));
        $svc->archetypeGet($archetypeId, 'adl');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function archetypeIdFormProvider(): array
    {
        return [
            'major version' => ['openEHR-EHR-OBSERVATION.blood_pressure.v1'],
            'full version' => ['openEHR-EHR-OBSERVATION.blood_pressure.v1.0.2'],
            'specialisation' => ['openEHR-EHR-OBSERVATION.lab_test-blood_glucose.v1'],
        ];
    }

    #[DataProvider('archetypeIdFormProvider')]
    public function testArchetypeGetOverGuzzleResolvesEachArchetypeIdFormUnchanged(string $archetypeId): void
    {
        $history = [];
        $svc = $this->serviceOverMockedGuzzle([
            new Response(200, [], '"1013.1.7850"'),
            new Response(200, ['Content-Type' => 'text/plain'], 'resolved ADL'),
        ], $history);

        $content = $svc->archetypeGet($archetypeId, 'adl');

        $this->assertCount(2, $history);
        $this->assertSame('/ckm/rest/v1/archetypes/citeable-identifier/' . $archetypeId, $history[0]['request']->getUri()->getPath());
        $this->assertSame('', $history[0]['request']->getUri()->getQuery());
        $this->assertSame('/ckm/rest/v1/archetypes/1013.1.7850/adl', $history[1]['request']->getUri()->getPath());
        $this->assertStringContainsString('resolved ADL', $content->text);
    }

    public function testArchetypeGetOverGuzzleTreatsA404AsAnUnknownArchetypeId(): void
    {
        $history = [];
        $svc = $this->serviceOverMockedGuzzle([new Response(404, [], 'Not Found')], $history);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Could not resolve "openEHR-EHR-OBSERVATION.unknown.v1" to a CKM citeable identifier (CID).');
        try {
            $svc->archetypeGet('openEHR-EHR-OBSERVATION.unknown.v1', 'adl');
        } catch (ToolCallException $e) {
            $this->assertCount(1, $history, 'No archetype request follows an unknown archetype-id');
            throw $e;
        }
    }

    /**
     * Statuses that say CKM failed, not that the archetype-id is unknown.
     *
     * @return array<string, array{int}>
     */
    public static function cidResolutionErrorStatusProvider(): array
    {
        return [
            'service unavailable' => [503],
            'internal server error' => [500],
            'bad gateway' => [502],
            'too many requests' => [429],
            'unauthorized' => [401],
        ];
    }

    #[DataProvider('cidResolutionErrorStatusProvider')]
    public function testArchetypeGetOverGuzzleNamesTheStatusWhenCidResolutionFails(int $status): void
    {
        $history = [];
        $svc = $this->serviceOverMockedGuzzle([new Response($status)], $history);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage(sprintf(
            'CKM answered HTTP %d while resolving "openEHR-EHR-OBSERVATION.blood_pressure.v1" to a citeable identifier (CID)',
            $status,
        ));
        try {
            $svc->archetypeGet('openEHR-EHR-OBSERVATION.blood_pressure.v1', 'adl');
        } catch (ToolCallException $e) {
            $this->assertCount(1, $history, 'No archetype request follows a failed resolution');
            throw $e;
        }
    }

    public function testArchetypeGetOverGuzzleReportsAnUnreachableCkmDuringCidResolution(): void
    {
        $connectFailure = new ConnectException(
            'cURL error 7: Failed to connect to ckm.example.test',
            new Request('GET', self::MOCK_CKM_BASE_URI . 'v1/archetypes/citeable-identifier/openEHR-EHR-OBSERVATION.blood_pressure.v1'),
        );
        $history = [];
        $svc = $this->serviceOverMockedGuzzle([$connectFailure], $history);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('CKM could not be reached while resolving "openEHR-EHR-OBSERVATION.blood_pressure.v1" to a citeable identifier (CID): cURL error 7');
        try {
            $svc->archetypeGet('openEHR-EHR-OBSERVATION.blood_pressure.v1', 'adl');
        } catch (ToolCallException $e) {
            $this->assertSame($connectFailure, $e->getPrevious());
            throw $e;
        }
    }

    public function testArchetypeSearchFetchesMoreThenSlicesToMaxResults(): void
    {
        $maxResults = 10;
        // Re-ranking window: multiplier 3.0, floored at FETCH_SIZE_MIN (30), capped at
        // FETCH_SIZE_LIMIT (60). 10 * 3.0 = 30, exactly the floor.
        $fetchSize = min(60, max(30, (int) ceil($maxResults * 3.0))); // 30
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

        $this->assertSame($fetchSize, (int) ($capturedQuery['size'] ?? 0), 'Request size should be 3.0× maxResults, floored and capped, for ranking');
        $this->assertSame(0, (int) ($capturedQuery['offset'] ?? -1), 'Request offset should always be 0');
        $this->assertCount($maxResults, $result['items'], 'Returned items should be sliced to maxResults');
    }

    /**
     * A small `maxResults` must not shrink the scored candidate set, or the ranking would
     * depend on how many results the caller asked for: the top 3 of a `maxResults` 3 search
     * would be drawn from a different candidate pool than the top 3 of a `maxResults` 10 one.
     * FETCH_SIZE_MIN keeps the whole 1..10 range on one window, so those agree.
     *
     * @return list<array{int}>
     */
    public static function smallMaxResultsProvider(): array
    {
        return [[1], [2], [3], [5], [8], [10]];
    }

    #[DataProvider('smallMaxResultsProvider')]
    public function testArchetypeSearchFloorsFetchSizeSoRankingIsStableForSmallMaxResults(int $maxResults): void
    {
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
        $svc->archetypeSearch('blood', $maxResults);

        $this->assertSame(
            30,
            (int) ($capturedQuery['size'] ?? 0),
            "maxResults={$maxResults} should still score FETCH_SIZE_MIN (30) candidates",
        );
    }

    #[DataProvider('smallMaxResultsProvider')]
    public function testTemplateSearchFloorsFetchSizeSoRankingIsStableForSmallMaxResults(int $maxResults): void
    {
        $capturedQuery = null;
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with('v1/templates', $this->callback(function (array $opts) use (&$capturedQuery): bool {
                $capturedQuery = $opts['query'] ?? [];
                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], '[]'));

        $svc = new CkmService($this->client, $this->logger);
        $svc->templateSearch('vital', $maxResults);

        $this->assertSame(
            30,
            (int) ($capturedQuery['size'] ?? 0),
            "maxResults={$maxResults} should still score FETCH_SIZE_MIN (30) candidates",
        );
    }

    /**
     * The property the fetch-size floor exists to guarantee: for a generic keyword, the top 3
     * of a `maxResults` 3 search are the first 3 of a `maxResults` 10 search. The upstream rows
     * are ordered so that the best-scoring candidate sits at CKM position 25 — reachable only
     * because the window no longer shrinks with `maxResults`.
     */
    public function testArchetypeSearchTopResultsDoNotDependOnMaxResults(): void
    {
        $payload = [];
        for ($i = 0; $i < 30; $i++) {
            // Low-relevance filler: keyword matches the display name only as a substring.
            $payload[] = [
                'cid' => "filler-{$i}",
                'resourceMainId' => "openEHR-EHR-OBSERVATION.bloodstream_{$i}.v1",
                'resourceMainDisplayName' => 'Bloodstream infection',
                'projectName' => 'Other',
                'status' => 'DRAFT',
            ];
        }
        // The strongest candidate, buried where a maxResults=3 window (9 rows) would never see it.
        $payload[25] = [
            'cid' => 'winner',
            'resourceMainId' => 'openEHR-EHR-OBSERVATION.blood.v1',
            'resourceMainDisplayName' => 'Blood',
            'projectName' => 'Common resources',
            'status' => 'PUBLISHED',
        ];

        // Honour the requested `size` the way CKM does, so a narrower window really does hide
        // the later rows — otherwise this test would pass even with the floor removed.
        $this->client
            ->method('get')
            ->willReturnCallback(function (string $path, array $opts) use ($payload): Response {
                $size = (int) ($opts['query']['size'] ?? count($payload));

                return new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode(array_slice($payload, 0, $size), JSON_THROW_ON_ERROR),
                );
            });

        $svc = new CkmService($this->client, $this->logger);
        $narrow = $svc->archetypeSearch('blood', 3);
        $wide = $svc->archetypeSearch('blood', 10);

        $this->assertSame('winner', $narrow['items'][0]['cid'] ?? null, 'The best candidate must survive a small maxResults');
        $this->assertSame(
            array_column($narrow['items'], 'cid'),
            array_slice(array_column($wide['items'], 'cid'), 0, 3),
            'top-3 of maxResults=3 must be the first 3 of maxResults=10',
        );
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

    public function testArchetypeSearchWrapsAClientExceptionAsToolCallException(): void
    {
        // The double extends \RuntimeException, and so does ToolCallException; expecting the
        // narrower ToolCallException, chained to the original, is what makes the wrap observable.
        $exception = new class('boom') extends \RuntimeException implements ClientExceptionInterface {
        };

        $this->client
            ->expects($this->once())
            ->method('get')
            ->with('v1/archetypes', $this->anything())
            ->willThrowException($exception);

        $svc = new CkmService($this->client, $this->logger);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Failed to search for CKM Archetypes: boom');
        try {
            $svc->archetypeSearch('x');
        } catch (ToolCallException $e) {
            $this->assertSame($exception, $e->getPrevious());
            throw $e;
        }
    }

    public function testArchetypeSearchOverGuzzleSendsItsQueryAndAcceptHeader(): void
    {
        $payload = [
            ['cid' => '1013.1.7850', 'resourceMainId' => 'openEHR-EHR-OBSERVATION.blood_pressure.v1', 'resourceMainDisplayName' => 'Blood pressure', 'projectName' => 'Common resources', 'status' => 'PUBLISHED'],
        ];
        $history = [];
        $svc = $this->serviceOverMockedGuzzle([
            new Response(200, ['Content-Type' => 'application/json', 'X-Total-Count' => '42'], json_encode($payload, JSON_THROW_ON_ERROR)),
        ], $history);

        $result = $svc->archetypeSearch('blood pressure', 10, false);

        $this->assertCount(1, $history, 'Exactly one request should reach the transport');
        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/ckm/rest/v1/archetypes', $request->getUri()->getPath());
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertStringContainsString('search-text=blood%20pressure', $request->getUri()->getQuery(), 'The query is RFC 3986 encoded');
        $this->assertSame([
            'offset' => '0',
            'require-all-search-words' => 'false',
            'restrict-search-to-main-data' => 'true',
            'search-text' => 'blood pressure',
            'size' => '30',
            'sort-key' => 'RELEVANCE',
        ], $this->sortedQuery($request));

        $this->assertSame('1013.1.7850', $result['items'][0]['cid']);
        $this->assertSame(42, $result['total']);
    }

    public function testTemplateSearchOverGuzzleSendsItsQueryAndAcceptHeader(): void
    {
        $payload = [
            ['cid' => '1013.26.244', 'resourceMainDisplayName' => 'Vital signs', 'projectName' => 'Common resources', 'status' => 'PUBLISHED'],
        ];
        $history = [];
        $svc = $this->serviceOverMockedGuzzle([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)),
        ], $history);

        $result = $svc->templateSearch('vital', 10);

        $this->assertCount(1, $history, 'Exactly one request should reach the transport');
        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/ckm/rest/v1/templates', $request->getUri()->getPath());
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame([
            'offset' => '0',
            'require-all-search-words' => 'true',
            'restrict-search-to-main-data' => 'true',
            'search-text' => 'vital',
            'size' => '30',
            'sort-key' => 'RELEVANCE',
            'template-type' => 'NORMAL',
        ], $this->sortedQuery($request));

        $this->assertSame('1013.26.244', $result['items'][0]['cid']);
    }

    public function testArchetypeSearchOverGuzzleWrapsAConnectFailureAsToolCallException(): void
    {
        $connectFailure = new ConnectException(
            'cURL error 7: Failed to connect to ckm.example.test',
            new Request('GET', self::MOCK_CKM_BASE_URI . 'v1/archetypes'),
        );
        $history = [];
        $svc = $this->serviceOverMockedGuzzle([$connectFailure], $history);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Failed to search for CKM Archetypes: cURL error 7');
        try {
            $svc->archetypeSearch('blood');
        } catch (ToolCallException $e) {
            $this->assertSame($connectFailure, $e->getPrevious());
            throw $e;
        }
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
        // Re-ranking window: multiplier 3.0, floored at FETCH_SIZE_MIN (30), capped at
        // FETCH_SIZE_LIMIT (60). 8 * 3.0 = 24, so the floor applies here.
        $fetchSize = min(60, max(30, (int) ceil($maxResults * 3.0))); // 30

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

        $this->assertSame($fetchSize, (int) ($capturedQuery['size'] ?? 0), 'Request size should be 3.0× maxResults, floored and capped, for ranking');
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

    /**
     * Template identifiers that are not exactly one path segment. Put into the request path
     * as given, each one made Guzzle resolve the request against `base_uri` to another page
     * on the CKM host, e.g. `../../../admin/secret` fetched `/ckm/admin/secret/oet`.
     *
     * @return array<string, array{string}>
     */
    public static function templateIdentifiersThatAreNotOnePathSegmentProvider(): array
    {
        return [
            'parent traversal' => ['../../../admin/secret'],
            'query' => ['x?evil=1'],
            'query and empty fragment' => ['x?evil=1#'],
            'fragment' => ['x#top'],
            'slash' => ['1013.26.244/opt'],
            'backslash' => ['..\\admin'],
            'percent escape' => ['%2e%2e'],
            'dot' => ['.'],
            'dot dot' => ['..'],
            'empty' => [''],
            'whitespace only' => ["  \t "],
            'inner control character' => ["my\x00template"],
            'inner newline' => ["my\ntemplate"],
        ];
    }

    #[DataProvider('templateIdentifiersThatAreNotOnePathSegmentProvider')]
    public function testTemplateGetRejectsAnIdentifierThatIsNotOnePathSegment(string $identifier): void
    {
        $this->client->expects($this->never())->method('get');
        $svc = new CkmService($this->client, $this->logger);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid CKM template identifier');
        $svc->templateGet($identifier, 'oet');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function templateIdentifierPathProvider(): array
    {
        return [
            'CID' => ['1013.26.244', '/ckm/rest/v1/templates/1013.26.244/oet'],
            'CID with surrounding whitespace' => [" 1013.26.244\n", '/ckm/rest/v1/templates/1013.26.244/oet'],
            'template name' => ['my_template', '/ckm/rest/v1/templates/my_template/oet'],
            'template name with spaces and parentheses' => ['Vital signs (encounter)', '/ckm/rest/v1/templates/Vital%20signs%20%28encounter%29/oet'],
        ];
    }

    #[DataProvider('templateIdentifierPathProvider')]
    public function testTemplateGetOverGuzzleRequestsTheIdentifierAsOneEncodedSegment(string $identifier, string $expectedPath): void
    {
        $history = [];
        $svc = $this->serviceOverMockedGuzzle([
            new Response(200, ['Content-Type' => 'application/xml'], '<template/>'),
        ], $history);

        $content = $svc->templateGet($identifier, 'oet');

        $this->assertCount(1, $history);
        $this->assertSame($expectedPath, $history[0]['request']->getUri()->getPath());
        $this->assertSame('', $history[0]['request']->getUri()->getQuery());
        $this->assertStringContainsString('<template/>', $content->text);
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

    /**
     * Digits, punctuation and inner spaces are not a valid RM class token.
     *
     * @return array<string, array{string}>
     */
    public static function malformedRmClassProvider(): array
    {
        return [
            'dash and punctuation' => ['comp-osition!'],
            'dash and digit' => ['OBS-1'],
            'leading digit' => ['1OBSERVATION'],
            'inner space' => ['OBS ERVATION'],
        ];
    }

    #[DataProvider('malformedRmClassProvider')]
    public function testArchetypeSearchRejectsMalformedRmClass(string $rmClass): void
    {
        // The format check runs before the search; a malformed filter sends no request.
        $this->client->expects($this->never())->method('get');
        $svc = new CkmService($this->client, $this->logger);
        // ToolCallException, not InvalidArgumentException: `CallToolHandler` maps only the
        // former to a tool-level error carrying its message, and replaces everything else
        // with a generic protocol error that most clients never show the model.
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid RM class filter');
        $svc->archetypeSearch('summary', 10, true, $rmClass);
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

    /**
     * The service over the real CkmClient and a real Guzzle client whose transport is a
     * MockHandler, so CkmService's request options go through Guzzle as they do in production.
     * Every request is recorded in $history; nothing reaches the network.
     *
     * @param list<ResponseInterface|\Throwable> $queue
     * @param array<int, array{request: RequestInterface, response: ?ResponseInterface, error: mixed, options: array<string, mixed>}> $history
     */
    private function serviceOverMockedGuzzle(array $queue, array &$history): CkmService
    {
        $stack = HandlerStack::create(new MockHandler($queue));
        $stack->push(Middleware::history($history));
        $guzzle = new Client(['handler' => $stack, 'base_uri' => self::MOCK_CKM_BASE_URI]);

        return new CkmService(new CkmClient(new NullLogger(), $guzzle), $this->logger);
    }

    /**
     * The request's query string decoded and sorted by key, so the assertion pins what CKM
     * receives without depending on parameter order.
     *
     * @return array<string, mixed>
     */
    private function sortedQuery(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);
        ksort($query);

        return $query;
    }
}
