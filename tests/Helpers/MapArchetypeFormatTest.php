<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Helpers;

use Cadasto\OpenEHR\MCP\Assistant\Helpers\Map;
use PHPUnit\Framework\TestCase;

final class MapArchetypeFormatTest extends TestCase
{
    public function testArchetypeFormatMappings(): void
    {
        $this->assertSame('adl', Map::archetypeFormat('adl'));
        $this->assertSame('xml', Map::archetypeFormat('xml'));
        $this->assertSame('mindmap', Map::archetypeFormat('mindmap'));
    }

    public function testArchetypeFormatThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Map::archetypeFormat('invalid');
    }
}
