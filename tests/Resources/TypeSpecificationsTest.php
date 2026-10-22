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
    private ?string $dir = null;

    protected function tearDown(): void
    {
        if ($this->dir === null) {
            return;
        }
        foreach (glob($this->dir . '/RM/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir . '/RM');
        rmdir($this->dir);
    }

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

    public function testMalformedJsonKeepsTheDecodeErrorAsItsCause(): void
    {
        $specifications = new TypeSpecifications($this->bmmDirWith('BROKEN', '{"name": "BROKEN",'));

        try {
            $specifications->read('RM', 'BROKEN');
            $this->fail('Expected a ResourceReadException for a malformed type specification.');
        } catch (ResourceReadException $e) {
            $this->assertSame('Unable to decode Type specification RM/BROKEN content.', $e->getMessage());
            $this->assertInstanceOf(\JsonException::class, $e->getPrevious());
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function documentsThatAreNotATypeObject(): array
    {
        return [
            'empty object' => ['{}'],
            'empty list' => ['[]'],
            'null' => ['null'],
            'string' => ['"COMPOSITION"'],
            'list' => ['[{"name": "COMPOSITION"}]'],
        ];
    }

    #[DataProvider('documentsThatAreNotATypeObject')]
    public function testValidJsonThatIsNotATypeObjectIsReportedAsSuch(string $json): void
    {
        $specifications = new TypeSpecifications($this->bmmDirWith('ODD', $json));

        $this->expectException(ResourceReadException::class);
        $this->expectExceptionMessageMatches('/^Type specification RM\/ODD is empty or not a JSON object\.$/');

        $specifications->read('RM', 'ODD');
    }

    private function bmmDirWith(string $name, string $json): string
    {
        $this->dir = sys_get_temp_dir() . '/type-specifications-' . bin2hex(random_bytes(6));
        mkdir($this->dir . '/RM', recursive: true);
        file_put_contents($this->dir . "/RM/$name.bmm.json", $json);

        return $this->dir;
    }
}
