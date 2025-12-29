<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Resources;

use Cadasto\OpenEHR\MCP\Assistant\Resources\Guidelines;
use Mcp\Exception\ResourceReadException;
use Mcp\Server\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Guidelines::class)]
final class GuidelinesTest extends TestCase
{
    public function test_can_read_known_guideline_markdown(): void
    {
        $reader = new Guidelines();
        $content = $reader->read('archetypes', 'v1', 'checklist');

        $this->assertIsString($content);
        $this->assertNotSame('', $content);
        $this->assertStringContainsStringIgnoringCase('archetype', $content);
    }

    public function test_can_read_template_guideline(): void
    {
        $reader = new Guidelines();
        $content = $reader->read('templates', 'v1', 'checklist');

        $this->assertIsString($content);
        $this->assertNotSame('', $content);
        $this->assertStringContainsStringIgnoringCase('template', $content);
    }


    public function test_cant_read_unknown_guideline(): void
    {
        $reader = new Guidelines();
        $this->expectException(ResourceReadException::class);
        $reader->read('archetypes', 'v1', 'unknown');
    }

    public function test_addResources_registers_guidelines_as_mcp_resources(): void
    {

        $builder = new Builder();

        Guidelines::addResources($builder);

        $ref = new \ReflectionClass($builder);
        $prop = $ref->getProperty('resources');
        $resources = $prop->getValue($builder);

        $this->assertIsArray($resources);
        $this->assertNotEmpty($resources, 'Expected at least one guideline resource to be registered.');

        $found = null;
        foreach ($resources as $resource) {
            if (($resource['uri'] ?? null) === 'openehr://guidelines/archetypes/v1/checklist') {
                $found = $resource;
                break;
            }
        }

        $this->assertIsArray($found, 'Expected openehr://guidelines/archetypes/v1/checklist to be registered as a resource.');

        $this->assertArrayHasKey('handler', $found);
        $this->assertInstanceOf(\Closure::class, $found['handler']);

        $content = ($found['handler'])();
        $this->assertIsString($content);
        $this->assertNotSame('', $content);

        $this->assertSame('text/markdown', $found['mimeType'] ?? null);
        $this->assertSame(\strlen($content), $found['size'] ?? null);

        $this->assertMatchesRegularExpression('/^guideline_[\w-]+_[\w-]+$/', (string)($found['name'] ?? ''));
        $this->assertNotSame('', (string)($found['description'] ?? ''));
    }


}
