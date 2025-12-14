<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Helpers;

use Cadasto\OpenEHR\MCP\Assistant\Helpers\Map;
use PHPUnit\Framework\TestCase;

final class MapTest extends TestCase
{
    public function testContentTypeMappings(): void
    {
        $this->assertSame('application/json', Map::contentType('json'));
        $this->assertSame('application/json', Map::contentType('application/json'));
        $this->assertSame('application/openehr.wt+json', Map::contentType('web template'));
        $this->assertSame('application/openehr.wt.flat.schema+json', Map::contentType('flat'));
        $this->assertSame('application/openehr.wt.structured.schema+json', Map::contentType('structured'));
        $this->assertSame('application/xml', Map::contentType('opt'));
        $this->assertSame('application/xml', Map::contentType('xml'));
        $this->assertSame('application/xml', Map::contentType('application/xml'));
        $this->assertSame('application/xml', Map::contentType('canonical'));
        $this->assertSame('application/xml', Map::contentType('mindmap'));
    }

    public function testContentTypeThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Map::contentType('invalid-format');
    }

    public function testAdlVersionMappings(): void
    {
        $this->assertSame('adl1.4', Map::adlVersion('adl1.4'));
        $this->assertSame('adl1.4', Map::adlVersion('adl'));
        $this->assertSame('adl2', Map::adlVersion('adl2'));
    }

    public function testAdlVersionThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Map::adlVersion('unknown');
    }
}
