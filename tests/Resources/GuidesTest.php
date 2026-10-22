<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Resources;

use Cadasto\OpenEHR\MCP\Assistant\Resources\Guides;
use Mcp\Exception\ResourceReadException;
use Mcp\Server\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array{string, string}>
     */
    public static function dotOnlySegments(): array
    {
        return [
            // resources/server-instructions.md exists one level above the guides directory.
            'parent directory as category' => ['..', 'server-instructions'],
            'current directory as category' => ['.', 'checklist'],
            'three dots as category' => ['...', 'checklist'],
            'parent directory as name' => ['archetypes', '..'],
        ];
    }

    #[DataProvider('dotOnlySegments')]
    public function test_rejects_a_segment_made_only_of_dots(string $category, string $name): void
    {
        $this->expectException(ResourceReadException::class);
        $this->expectExceptionMessage('Invalid guide resource identifier');

        (new Guides())->read($category, $name);
    }

    public function test_can_read_a_guide_whose_name_contains_dots(): void
    {
        $content = (new Guides())->read('specs', 'am-ADL1.4');

        $this->assertNotSame('', $content);
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

    public function test_addResources_skips_readme_and_underscore_prefixed_files(): void
    {
        $builder = new Builder();
        Guides::addResources($builder);

        $ref = new \ReflectionClass($builder);
        $prop = $ref->getProperty('resources');
        $resources = $prop->getValue($builder);
        $uris = array_map(static fn(array $r): string => (string)($r['uri'] ?? ''), (array)$resources);

        foreach ($uris as $uri) {
            $this->assertDoesNotMatchRegularExpression(
                '#/README$|/_[^/]+$#',
                $uri,
                sprintf('Guide registry leaked authoring artifact: %s', $uri)
            );
        }
    }
}
