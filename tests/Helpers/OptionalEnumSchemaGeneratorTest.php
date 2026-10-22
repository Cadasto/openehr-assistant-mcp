<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Helpers;

use Cadasto\OpenEHR\MCP\Assistant\Helpers\OptionalEnumSchemaGenerator;
use Mcp\Capability\Attribute\Schema;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Capability\Discovery\SchemaGeneratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OptionalEnumSchemaGenerator::class)]
final class OptionalEnumSchemaGeneratorTest extends TestCase
{
    /**
     * @return array<string, array{0: \Closure}>
     */
    public static function optionalNullableEnumProvider(): array
    {
        return [
            'enum lists null' => [
                static fn (#[Schema(enum: ['a', 'b', null])] ?string $x = null): null => null,
            ],
            'enum lists strings only' => [
                static fn (#[Schema(enum: ['a', 'b'])] ?string $x = null): null => null,
            ],
        ];
    }

    #[DataProvider('optionalNullableEnumProvider')]
    public function test_REQ_N9_optional_nullable_enum_is_published_as_strings_only(\Closure $fixture): void
    {
        $raw = self::rawSchema($fixture);
        $this->assertSame(['null', 'string'], $raw['properties']['x']['type'], 'Fixture precondition: the SDK renders the parameter as nullable');

        $schema = (new OptionalEnumSchemaGenerator())->generate(new \ReflectionFunction($fixture));

        $this->assertSame(['type' => 'string', 'enum' => ['a', 'b']], $schema['properties']['x']);
        $this->assertArrayNotHasKey('required', $schema);
    }

    public function test_REQ_N9_required_nullable_enum_is_left_unchanged(): void
    {
        $fixture = static fn (#[Schema(enum: ['a', 'b', null])] ?string $x): null => null;
        $raw = self::rawSchema($fixture);
        $this->assertSame(['x'], $raw['required'] ?? null, 'Fixture precondition: the parameter is required');
        $this->assertContains(null, $raw['properties']['x']['enum'], 'Fixture precondition: the enum lists null');

        $schema = (new OptionalEnumSchemaGenerator())->generate(new \ReflectionFunction($fixture));

        $this->assertSame($raw, $schema);
    }

    public function test_REQ_N9_optional_nullable_parameter_without_enum_keeps_its_nullable_type_and_default(): void
    {
        $fixture = static fn (?string $x = null): null => null;
        $raw = self::rawSchema($fixture);

        $schema = (new OptionalEnumSchemaGenerator())->generate(new \ReflectionFunction($fixture));

        $this->assertSame($raw, $schema);
        $this->assertSame(['null', 'string'], $schema['properties']['x']['type']);
        $this->assertArrayHasKey('default', $schema['properties']['x']);
        $this->assertNull($schema['properties']['x']['default']);
    }

    public function test_REQ_N9_optional_enum_with_a_non_null_default_is_left_unchanged(): void
    {
        $fixture = static fn (#[Schema(enum: ['a', 'b'])] string $x = 'a'): null => null;
        $raw = self::rawSchema($fixture);

        $schema = (new OptionalEnumSchemaGenerator())->generate(new \ReflectionFunction($fixture));

        $this->assertSame($raw, $schema);
        $this->assertSame('a', $schema['properties']['x']['default'] ?? null);
        $this->assertSame(['a', 'b'], $schema['properties']['x']['enum']);
    }

    public function test_REQ_N9_method_level_keys_pass_through(): void
    {
        $fixture = new class () {
            #[Schema(additionalProperties: false)]
            public function run(
                string $query,
                #[Schema(enum: ['a', 'b'])]
                ?string $kind = null,
            ): void {
            }
        };

        $schema = (new OptionalEnumSchemaGenerator())->generate(new \ReflectionMethod($fixture, 'run'));

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('additionalProperties', $schema);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertSame(['query'], $schema['required'] ?? null);
        $this->assertSame(['type' => 'string'], $schema['properties']['query']);
        $this->assertSame(['type' => 'string', 'enum' => ['a', 'b']], $schema['properties']['kind']);
    }

    public function test_REQ_N9_schema_without_parameters_passes_through(): void
    {
        $fixture = static fn (): null => null;
        $raw = self::rawSchema($fixture);

        $schema = (new OptionalEnumSchemaGenerator())->generate(new \ReflectionFunction($fixture));

        $this->assertEquals($raw, $schema, 'An empty properties object must stay an object');
        $this->assertInstanceOf(\stdClass::class, $schema['properties']);
    }

    public function test_REQ_N9_schema_definition_without_properties_passes_through(): void
    {
        // A method-level `#[Schema(definition: ...)]` is published as written.
        $definition = ['type' => 'object', 'additionalProperties' => true];
        $inner = $this->createStub(SchemaGeneratorInterface::class);
        $inner->method('generate')->willReturn($definition);

        $schema = (new OptionalEnumSchemaGenerator($inner))->generate(new \ReflectionFunction(static fn (): null => null));

        $this->assertSame($definition, $schema);
    }

    public function test_REQ_N9_union_type_keeps_its_other_types_as_a_list(): void
    {
        $inner = $this->createStub(SchemaGeneratorInterface::class);
        $inner->method('generate')->willReturn([
            'type' => 'object',
            'properties' => [
                'x' => ['type' => ['null', 'string', 'integer'], 'default' => null, 'enum' => ['a', 1, null]],
            ],
            'additionalProperties' => false,
        ]);

        $schema = (new OptionalEnumSchemaGenerator($inner))->generate(new \ReflectionFunction(static fn (): null => null));

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'x' => ['type' => ['string', 'integer'], 'enum' => ['a', 1]],
            ],
            'additionalProperties' => false,
        ], $schema);
    }

    /**
     * @return array<string, array{0: array<string, mixed>|null}>
     */
    public static function outputSchemaProvider(): array
    {
        return [
            'a declared output schema' => [[
                'type' => 'object',
                'properties' => ['items' => ['type' => ['null', 'string'], 'enum' => ['a', null], 'default' => null]],
                'additionalProperties' => false,
            ]],
            'no output schema' => [null],
        ];
    }

    /**
     * @param array<string, mixed>|null $outputSchema
     */
    #[DataProvider('outputSchemaProvider')]
    public function test_REQ_N9_output_schema_passes_through_unchanged(?array $outputSchema): void
    {
        $inner = $this->createStub(SchemaGeneratorInterface::class);
        $inner->method('generateOutputSchema')->willReturn($outputSchema);

        $schema = (new OptionalEnumSchemaGenerator($inner))->generateOutputSchema(new \ReflectionFunction(static fn (): null => null));

        $this->assertSame($outputSchema, $schema);
    }

    /**
     * The schema the SDK's own generator produces, before this server's wrapper.
     *
     * @return array<string, mixed>
     */
    private static function rawSchema(\Closure $fixture): array
    {
        return (new SchemaGenerator(new DocBlockParser()))->generate(new \ReflectionFunction($fixture));
    }
}
