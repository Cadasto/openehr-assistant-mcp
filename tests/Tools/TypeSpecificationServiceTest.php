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
        $this->assertContains('COMPOSITION', $content);
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
}
