<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService;
use Mcp\Schema\Content\TextContent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(TypeSpecificationService::class)]
final class TypeSpecificationServiceTest extends TestCase
{
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new NullLogger();
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::getCandidateFiles
     */
    public function test_getCandidateFiles_matches_exact_file(): void
    {
        $svc = new TypeSpecificationService($this->logger);

        $generator = $svc->getCandidateFiles('LOCATABLE');
        $results = iterator_to_array($generator);

        $this->assertCount(1, $results);
        $this->assertEquals(APP_RESOURCES_DIR . '/bmm/RM/LOCATABLE.bmm.json', $results[0]->getPathname());
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::getCandidateFiles
     */
    public function test_getCandidateFiles_ignores_non_matching_files(): void
    {

        $svc = new TypeSpecificationService($this->logger);

        $generator = $svc->getCandidateFiles('file1');
        $results = iterator_to_array($generator);
        $this->assertCount(0, $results);
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::getCandidateFiles
     */
    public function test_getCandidateFiles_handles_empty_directory(): void
    {
        $svc = new TypeSpecificationService($this->logger);

        $generator = $svc->getCandidateFiles('*');
        $results = iterator_to_array($generator);

        $this->assertGreaterThan(0, count($results));
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::search
     */
    public function test_list_with_pattern_returns_array_of_items(): void
    {
        $svc = new TypeSpecificationService($this->logger);
        $results = $svc->search('*archetype*');
        $this->assertIsArray($results);
        $this->assertArrayHasKey('items', $results);
        $this->assertIsArray($results['items']);
        if (count($results['items']) > 0) {
            $first = $results['items'][0];
            $this->assertArrayHasKey('name', $first);
            $this->assertArrayHasKey('documentation', $first);
            $this->assertArrayHasKey('resourceUri', $first);
            $this->assertArrayHasKey('component', $first);
            $this->assertArrayHasKey('package', $first);
        }
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::search
     */
    public function test_list_with_keyword_filters_content(): void
    {
        $svc = new TypeSpecificationService($this->logger);
        $results = $svc->search('*', 'archetype');
        $this->assertIsArray($results);
        $this->assertArrayHasKey('items', $results);
        $this->assertIsArray($results['items']);
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::search
     */
    public function test_list_with_short_pattern_returns_empty_items_shape(): void
    {
        $svc = new TypeSpecificationService($this->logger);
        $results = $svc->search('dv');
        $this->assertSame(['items' => [], 'total' => 0], $results);
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::search
     */
    public function test_list_composition_returns_single(): void
    {
        $svc = new TypeSpecificationService($this->logger);
        $results = $svc->search('composition');
        $this->assertIsArray($results);
        $this->assertArrayHasKey('items', $results);
        $this->assertIsArray($results['items']);
        $this->assertCount(1, $results['items']);
        $first = $results['items'][0];
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('documentation', $first);
        $this->assertArrayHasKey('component', $first);
        $this->assertArrayHasKey('resourceUri', $first);
        $this->assertArrayHasKey('package', $first);
        $this->assertSame('COMPOSITION', $first['name']);
        $this->assertSame('RM', $first['component']);
        $this->assertSame('openehr://spec/type/RM/COMPOSITION', $first['resourceUri']);
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::get
     */
    public function test_get_by_identifier_returns_json_content(): void
    {
        $svc = new TypeSpecificationService($this->logger);
        $content = $svc->get('COMPOSITION');
        $this->assertIsArray($content);
        $this->assertSame('COMPOSITION', $content['name'] ?? null);
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::get
     */
    public function test_get_by_identifier_accepts_lowercase_component(): void
    {
        $svc = new TypeSpecificationService($this->logger);
        $content = $svc->get('COMPOSITION', 'rm');
        $this->assertSame('COMPOSITION', $content['name'] ?? null);
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::get
     */
    public function test_get_by_nonexistent_identifier_throws_exception(): void
    {
        $svc = new TypeSpecificationService($this->logger);
        $this->expectException(\RuntimeException::class);
        $content = $svc->get('this_type_does_not_exist');
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::get
     */
    public function test_get_synthesises_the_declared_resource_uri(): void
    {
        // `resourceUri` is declared `required` in the outputSchema but appears in none of the
        // bundled BMM documents, so the value — not merely its presence — is pinned here.
        $svc = new TypeSpecificationService($this->logger);

        $this->assertSame('openehr://spec/type/RM/COMPOSITION', $svc->get('COMPOSITION')['resourceUri'] ?? null);
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::get
     */
    public function test_get_and_search_agree_on_the_resource_uri_for_the_same_type(): void
    {
        // `get()` derived the component with bare basename() while `search()` upper-cased it;
        // the two matched only because resources/bmm/ happens to hold upper-case directories.
        $svc = new TypeSpecificationService($this->logger);

        $fromGet = $svc->get('COMPOSITION')['resourceUri'] ?? null;
        $fromSearch = $svc->search('composition')['items'][0]['resourceUri'] ?? null;

        $this->assertSame($fromSearch, $fromGet);
    }

    /**
     * @covers \Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::get
     */
    public function test_get_always_returns_the_schema_required_keys(): void
    {
        // The outputSchema declares `name` and `resourceUri` required. `name` comes from the
        // BMM document, so assert it is resolved rather than assumed. (The malformed-document
        // guards in get() are defensive only: BMM_DIR is a compile-time constant, so a
        // corrupt corpus cannot be injected without restructuring the class.)
        $svc = new TypeSpecificationService($this->logger);
        $content = $svc->get('DV_QUANTITY');

        $this->assertArrayHasKey('name', $content);
        $this->assertIsString($content['name']);
        $this->assertNotSame('', $content['name']);
        $this->assertArrayHasKey('resourceUri', $content);
        $this->assertStringStartsWith('openehr://spec/type/', (string) $content['resourceUri']);
    }
}
