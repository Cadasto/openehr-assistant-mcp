<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\ArchetypeDesignerClient;
use Cadasto\OpenEHR\MCP\Assistant\Tools\ArchetypeDesignerService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\NullLogger;

/**
 * Drives the service through a real Guzzle client on a MockHandler, so query strings,
 * headers and bodies are asserted as they would go on the wire — and nothing goes live.
 */
#[CoversClass(ArchetypeDesignerService::class)]
final class ArchetypeDesignerServiceTest extends TestCase
{
    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    /**
     * @param list<Response|\Throwable> $responses
     */
    private function service(array $responses, string $username = 'user', string $defaultRepositoryId = 'repo-default'): ArchetypeDesignerService
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $client = new ArchetypeDesignerClient(
            new NullLogger(),
            new Client(['handler' => $stack, 'base_uri' => 'https://designer.test/designer/']),
            $username,
            'secret',
            $defaultRepositoryId,
            'gh-token',
        );

        return new ArchetypeDesignerService($client, new NullLogger());
    }

    private function request(int $i): RequestInterface
    {
        return $this->history[$i]['request'];
    }

    /**
     * @return array<string, mixed>
     */
    private function query(int $i): array
    {
        parse_str($this->request($i)->getUri()->getQuery(), $query);

        return $query;
    }

    public function testRepositoryListMapsRows(): void
    {
        $svc = $this->service([new Response(200, [], json_encode([
            ['id' => 'r1', 'name' => 'Vital signs', 'type' => 'github', 'owner' => 'ian', 'config' => ['x' => 1]],
            'not-a-row',
        ], JSON_THROW_ON_ERROR))]);

        $result = $svc->repositoryList();

        $this->assertSame(['items' => [['id' => 'r1', 'name' => 'Vital signs', 'type' => 'github', 'owner' => 'ian']], 'total' => 1], $result);
        $this->assertSame('/designer/api/repository/list', $this->request(0)->getUri()->getPath());
        $this->assertSame('gh-token', $this->request(0)->getHeaderLine('Repository-Token'));
    }

    public function testEntryListUsesDefaultRepositoryAndTypeFilter(): void
    {
        $svc = $this->service([new Response(200, [], json_encode([
            ['type' => 'archetype', 'path' => 'local/bp.adls', 'name' => 'Blood pressure', 'archetypeId' => 'openEHR-EHR-OBSERVATION.blood_pressure.v2', 'rmType' => 'OBSERVATION', 'status' => 'valid'],
        ], JSON_THROW_ON_ERROR))]);

        $result = $svc->entryList(type: 'archetype');

        $this->assertSame(1, $result['total']);
        $this->assertSame('openEHR-EHR-OBSERVATION.blood_pressure.v2', $result['items'][0]['archetypeId']);
        $this->assertSame(['repositoryId' => 'repo-default', 'path' => '', 'type' => 'archetype'], $this->query(0));
    }

    public function testSearchPassesTextAndLimit(): void
    {
        $svc = $this->service([new Response(200, [], json_encode(['hits' => [
            ['path' => 'local/bp.adls', 'class' => 'archetype'],
            ['path' => 'local/vs.t.json', 'class' => 'template'],
        ]], JSON_THROW_ON_ERROR))]);

        $result = $svc->search('blood pressure', 'repo-x', 1);

        $this->assertSame(['items' => [['path' => 'local/bp.adls', 'class' => 'archetype']], 'total' => 1], $result);
        $this->assertSame(['repositoryId' => 'repo-x', 'text' => 'blood pressure', 'limit' => '1'], $this->query(0));
    }

    public function testArchetypeGetAdlPostsTheModelToFormat(): void
    {
        $model = '{"archetypeId":{"value":"openEHR-EHR-OBSERVATION.blood_pressure.v2"},"otherMetaData":{}}';
        $svc = $this->service([new Response(200, [], $model), new Response(200, [], "archetype (adl_version=2.0.6)\n")]);

        $content = $svc->archetypeGet('openEHR-EHR-OBSERVATION.blood_pressure.v2');

        $this->assertStringContainsString('archetype (adl_version=2.0.6)', $content->text);
        $this->assertSame('/designer/api/repository/archetype/get', $this->request(0)->getUri()->getPath());
        $this->assertSame('openEHR-EHR-OBSERVATION.blood_pressure.v2', $this->query(0)['archetypeId']);
        $this->assertSame('POST', $this->request(1)->getMethod());
        $this->assertSame('/designer/api/archetype/format', $this->request(1)->getUri()->getPath());
        $this->assertSame('text/adl', $this->request(1)->getHeaderLine('Accept'));
        // The model goes back byte-for-byte, so `{}` is not turned into `[]`.
        $this->assertSame($model, (string)$this->request(1)->getBody());
    }

    public function testArchetypeGetJsonMakesOneCall(): void
    {
        $svc = $this->service([new Response(200, [], '{"a":1}')]);

        $content = $svc->archetypeGet('x', format: 'json');

        $this->assertStringContainsString('{"a":1}', $content->text);
        $this->assertCount(1, $this->history);
    }

    public function testTemplateGetWebTemplateUsesExportEndpointAndLanguage(): void
    {
        $svc = $this->service([new Response(200, [], '{"templateId":"Vital signs"}'), new Response(200, [], '{"tree":{}}')]);

        $content = $svc->templateGet('Vital signs', 'repo-x', 'web_template', 'nl');

        $this->assertStringContainsString('"tree"', $content->text);
        $this->assertSame('Vital signs', $this->query(0)['templateId']);
        $this->assertSame('/designer/api/template/export/wtp', $this->request(1)->getUri()->getPath());
        $this->assertSame(['repositoryId' => 'repo-x', 'primaryLanguage' => 'nl'], $this->query(1));
        $this->assertSame('application/wtp', $this->request(1)->getHeaderLine('Accept'));
    }

    public function testAnalyzeReturnsSummaryOnly(): void
    {
        $analysis = ['status' => 'recoverable', 'issues' => [], 'smells' => [['message' => 'unused term']], 'statistics' => ['big' => 1], 'directDependencies' => ['archetypes' => []]];
        $svc = $this->service([new Response(200, [], '{"templateId":"T"}'), new Response(200, [], json_encode($analysis, JSON_THROW_ON_ERROR))]);

        $content = $svc->analyze('template', 'T');

        $this->assertSame('/designer/api/repository/template/get', $this->request(0)->getUri()->getPath());
        $this->assertSame('/designer/api/template/analyze', $this->request(1)->getUri()->getPath());
        $this->assertStringContainsString('unused term', $content->text);
        $this->assertStringNotContainsString('statistics', $content->text);
    }

    public function testUnconfiguredServerFailsWithoutCallingUpstream(): void
    {
        $svc = $this->service([], username: '');

        try {
            $svc->repositoryList();
            $this->fail('Expected ToolCallException');
        } catch (ToolCallException $e) {
            $this->assertStringContainsString('ARCHETYPE_DESIGNER_USERNAME', $e->getMessage());
        }
        $this->assertCount(0, $this->history);
    }

    public function testMissingRepositoryIdIsAnActionableError(): void
    {
        $svc = $this->service([], defaultRepositoryId: '');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('designer_repository_list');
        $svc->entryList();
    }

    public function testTransportErrorBecomesToolCallException(): void
    {
        $svc = $this->service([new ConnectException('boom', new Request('GET', 'api/repository/list'))]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Failed to list Archetype Designer repositories: boom');
        $svc->repositoryList();
    }

    public function testUpstreamHttpErrorBecomesToolCallException(): void
    {
        $svc = $this->service([new Response(401, [], '{"message":"Unauthorized"}')]);

        $this->expectException(ToolCallException::class);
        $svc->repositoryList();
    }

    public function testInvalidJsonBecomesToolCallException(): void
    {
        $svc = $this->service([new Response(200, [], '<html>')]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('invalid JSON');
        $svc->repositoryList();
    }
}
