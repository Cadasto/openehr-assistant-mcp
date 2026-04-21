<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Resources;

use Cadasto\OpenEHR\MCP\Assistant\Resources\Examples;
use Mcp\Exception\ResourceReadException;
use Mcp\Server\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Examples::class)]
final class ExamplesTest extends TestCase
{
    public function test_can_read_known_aql_example(): void
    {
        $reader = new Examples();
        $content = $reader->read('aql', 'latest_blood_pressure_per_ehr');

        $this->assertIsString($content);
        $this->assertNotSame('', $content);
        $this->assertStringContainsString('```aql', $content);
    }

    public function test_can_read_flat_example(): void
    {
        $reader = new Examples();
        $content = $reader->read('flat', 'vital_signs_blood_pressure');

        $this->assertStringContainsString('"ctx/language"', $content);
        $this->assertStringContainsString('|magnitude', $content);
    }

    public function test_can_read_structured_example(): void
    {
        $reader = new Examples();
        $content = $reader->read('structured', 'vital_signs_blood_pressure');

        $this->assertStringContainsString('"ctx"', $content);
        $this->assertStringContainsString('"|magnitude"', $content);
    }

    public function test_unknown_example_raises(): void
    {
        $reader = new Examples();
        $this->expectException(ResourceReadException::class);
        $reader->read('aql', 'unknown_example_xyz');
    }

    public function test_invalid_identifier_raises(): void
    {
        $reader = new Examples();
        $this->expectException(ResourceReadException::class);
        $reader->read('aql', '../../../etc/passwd');
    }

    public function test_addResources_registers_examples(): void
    {
        $builder = new Builder();
        Examples::addResources($builder);

        $ref = new \ReflectionClass($builder);
        $prop = $ref->getProperty('resources');
        $resources = $prop->getValue($builder);

        $this->assertIsArray($resources);
        $this->assertNotEmpty($resources);

        $uris = array_map(static fn(array $r): string => (string)($r['uri'] ?? ''), (array)$resources);

        $this->assertContains('openehr://examples/aql/latest_blood_pressure_per_ehr', $uris);
        $this->assertContains('openehr://examples/flat/vital_signs_blood_pressure', $uris);
        $this->assertContains('openehr://examples/structured/vital_signs_blood_pressure', $uris);

        foreach ($uris as $uri) {
            $this->assertDoesNotMatchRegularExpression(
                '#/README$|/_[^/]+$#',
                $uri,
                sprintf('Examples registry leaked authoring artifact: %s', $uri)
            );
        }
    }
}
