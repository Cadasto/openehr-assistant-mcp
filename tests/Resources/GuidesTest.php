<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Resources;

use Cadasto\OpenEHR\MCP\Assistant\Resources\Guides;
use Mcp\Exception\ResourceReadException;
use Mcp\Server\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Guides::class)]
final class GuidesTest extends TestCase
{
    public function test_can_read_known_guide_markdown(): void
    {
        $reader = new Guides();
        $content = $reader->read('archetypes', 'checklist');

        $this->assertIsString($content);
        $this->assertNotSame('', $content);
        $this->assertStringContainsStringIgnoringCase('archetype', $content);
    }

    public function test_can_read_template_guide(): void
    {
        $reader = new Guides();
        $content = $reader->read('templates', 'checklist');

        $this->assertIsString($content);
        $this->assertNotSame('', $content);
        $this->assertStringContainsStringIgnoringCase('template', $content);
    }


    public function test_cant_read_unknown_guide(): void
    {
        $reader = new Guides();
        $this->expectException(ResourceReadException::class);
        $reader->read('archetypes', 'unknown');
    }

    public function test_addResources_registers_guides_as_mcp_resources(): void
    {

        $builder = new Builder();

        Guides::addResources($builder);

        $ref = new \ReflectionClass($builder);
        $prop = $ref->getProperty('resources');
        $resources = $prop->getValue($builder);

        $this->assertIsArray($resources);
        $this->assertNotEmpty($resources, 'Expected at least one guide resource to be registered.');

        $found = null;
        foreach ($resources as $resource) {
            if (($resource['uri'] ?? null) === 'openehr://guides/archetypes/checklist') {
                $found = $resource;
                break;
            }
        }

        $this->assertIsArray($found, 'Expected openehr://guides/archetypes/checklist to be registered as a resource.');

        $this->assertArrayHasKey('handler', $found);
        $this->assertInstanceOf(\Closure::class, $found['handler']);

        $content = ($found['handler'])();
        $this->assertIsString($content);
        $this->assertNotSame('', $content);

        $this->assertSame('text/markdown', $found['mimeType'] ?? null);
        $this->assertSame(\strlen($content), $found['size'] ?? null);

        $this->assertMatchesRegularExpression('/^guide_[\w-]+_[\w-]+$/', (string)($found['name'] ?? ''));
        $this->assertNotSame('', (string)($found['description'] ?? ''));
    }


}
