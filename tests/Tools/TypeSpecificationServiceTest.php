<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Mcp\Schema\Content\TextContent;

final class TypeSpecificationServiceTest extends TestCase
{
    private function makeLogger(): Logger
    {
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());
        return $logger;
    }

    public function test_list_with_pattern_returns_array_of_items(): void
    {
        $svc = new TypeSpecificationService($this->makeLogger());
        $results = $svc->search('*archetype*');
        $this->assertIsArray($results);
        if (count($results) > 0) {
            $first = $results[0];
            $this->assertArrayHasKey('type', $first);
            $this->assertArrayHasKey('description', $first);
            $this->assertArrayHasKey('component', $first);
            $this->assertArrayHasKey('file', $first);
        }
    }

    public function test_list_with_keyword_filters_content(): void
    {
        $svc = new TypeSpecificationService($this->makeLogger());
        $results = $svc->search('*', 'archetype');
        $this->assertIsArray($results);
    }

    public function test_list_composition_returns_single(): void
    {
        $svc = new TypeSpecificationService($this->makeLogger());
        $results = $svc->search('composition');
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $first = $results[0];
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('description', $first);
        $this->assertArrayHasKey('component', $first);
        $this->assertArrayHasKey('file', $first);
        $this->assertSame('COMPOSITION', $first['type']);
        $this->assertSame('RM', $first['component']);
    }

    public function test_get_by_identifier_returns_json_content(): void
    {
        $svc = new TypeSpecificationService($this->makeLogger());
        $content = $svc->get('COMPOSITION');
        $this->assertInstanceOf(TextContent::class, $content);
        $this->assertStringContainsString('COMPOSITION', $content->text);
        $this->assertStringContainsString('```', $content->text);
        $this->assertStringContainsString('application/json', $content->text);
    }

    public function test_get_by_nonexistent_identifier_returns_error_text(): void
    {
        $svc = new TypeSpecificationService($this->makeLogger());
        $content = $svc->get('this_type_does_not_exist');
        $this->assertInstanceOf(TextContent::class, $content);
        $this->assertStringContainsString('not found', $content->text);
        $this->assertStringContainsString('```', $content->text);
    }
}
