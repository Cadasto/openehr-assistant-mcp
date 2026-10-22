<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Resources;

use Cadasto\OpenEHR\MCP\Assistant\Resources\TypeSpecifications;
use Mcp\Exception\ResourceReadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypeSpecifications::class)]
final class TypeSpecificationsTest extends TestCase
{
    public function testReadReturnsDecodedArrayForKnownType(): void
    {
        $res = (new TypeSpecifications())->read('RM', 'COMPOSITION');
        $this->assertIsArray($res);
        $this->assertSame('COMPOSITION', $res['name'] ?? null);
        $this->assertArrayHasKey('properties', $res);
        $this->assertArrayHasKey('package', $res);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidIdentifiers(): array
    {
        return [
            'bad component' => ['RM!', 'COMPOSITION', 'Invalid component: RM!'],
            // resources/bmm/AM/ARCHETYPE.bmm.json exists, so only the name check stops this one.
            'bad name' => ['RM', '../AM/ARCHETYPE', 'Invalid type specification name: ../AM/ARCHETYPE'],
        ];
    }

    #[DataProvider('invalidIdentifiers')]
    public function testInvalidComponentAndNameAreRejected(string $component, string $name, string $message): void
    {
        // The SDK passes the message on to the client only for ResourceReadException.
        $this->expectException(ResourceReadException::class);
        $this->expectExceptionMessage($message);

        (new TypeSpecifications())->read($component, $name);
    }

    public function testMissingFileThrowsResourceReadException(): void
    {
        $this->expectException(ResourceReadException::class);
        (new TypeSpecifications())->read('RM', 'THIS_TYPE_DOES_NOT_EXIST');
    }
}
