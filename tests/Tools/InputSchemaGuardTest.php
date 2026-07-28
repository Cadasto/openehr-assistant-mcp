<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Tools\CkmService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\ExamplesService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\TerminologyService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * CI guard: reflects every `#[McpTool]` method through the SDK's own {@see SchemaGenerator}
 * (the same code path the server uses to publish input schemas) and asserts two
 * wire-visible invariants that are easy to silently regress when adding a parameter:
 *
 * - The generated input schema is closed (`additionalProperties: false`) at the
 *   method/object level, so clients can't silently pass unknown arguments.
 * - Optional (`?string $x = null`) parameters constrained by `#[Schema(enum: [...])]`
 *   advertise `null` as an allowed enum value, matching the nullable type the
 *   generator also infers — a self-consistent schema instead of a type/enum mismatch.
 */
#[CoversNothing]
final class InputSchemaGuardTest extends TestCase
{
    private static ?SchemaGenerator $generator = null;

    #[DataProvider('mcpToolMethodProvider')]
    public function test_input_schema_declares_additional_properties_false(string $class, string $method): void
    {
        $schema = $this->generateSchema($class, $method);

        $this->assertArrayHasKey('additionalProperties', $schema, sprintf('%s::%s input schema must declare additionalProperties', $class, $method));
        $this->assertFalse($schema['additionalProperties'], sprintf('%s::%s input schema must set additionalProperties: false', $class, $method));
    }

    /**
     * @param array<string, mixed> $paramSchema
     */
    #[DataProvider('nullableEnumParameterProvider')]
    public function test_nullable_enum_parameters_advertise_null_in_enum(string $label, array $paramSchema): void
    {
        $this->assertContains(
            null,
            $paramSchema['enum'],
            sprintf('%s allows null but its enum list omits null, so the published schema is self-inconsistent', $label),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function generateSchema(string $class, string $method): array
    {
        $reflection = new ReflectionMethod($class, $method);

        return self::schemaGenerator()->generate($reflection);
    }

    private static function schemaGenerator(): SchemaGenerator
    {
        return self::$generator ??= new SchemaGenerator(new DocBlockParser());
    }

    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function mcpToolMethodProvider(): array
    {
        // Discovered, not hardcoded: public/index.php registers src/Tools by directory, so a
        // new service is published to clients whether or not it is listed here.
        $classes = [];
        foreach (glob(__DIR__ . '/../../src/Tools/*.php') ?: [] as $path) {
            $class = 'Cadasto\\OpenEHR\\MCP\\Assistant\\Tools\\' . basename($path, '.php');
            if (class_exists($class)) {
                $classes[] = $class;
            }
        }
        self::assertNotEmpty($classes, 'No tool classes discovered under src/Tools');

        $cases = [];
        foreach ($classes as $class) {
            foreach ((new \ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                if ($reflectionMethod->getAttributes(McpTool::class) === []) {
                    continue;
                }
                $cases[$class . '::' . $reflectionMethod->getName()] = [$class, $reflectionMethod->getName()];
            }
        }

        return $cases;
    }

    /**
     * Yields exactly the nullable parameters that carry an `enum`, so the guard above always
     * has something real to assert. If the SDK ever stops rendering nullable types as a
     * `["null", ...]` union this provider empties out and PHPUnit fails the test rather than
     * reporting a vacuous pass.
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function nullableEnumParameterProvider(): array
    {
        $cases = [];
        foreach (self::mcpToolMethodProvider() as [$class, $method]) {
            $schema = self::schemaGenerator()->generate(new ReflectionMethod($class, $method));
            foreach ($schema['properties'] ?? [] as $paramName => $paramSchema) {
                $type = $paramSchema['type'] ?? null;
                $allowsNull = (is_array($type) && in_array('null', $type, true)) || $type === 'null';
                if (!$allowsNull || !isset($paramSchema['enum'])) {
                    continue;
                }
                $cases[sprintf('%s::%s($%s)', $class, $method, (string) $paramName)] = [
                    sprintf('%s::%s parameter "%s"', $class, $method, (string) $paramName),
                    $paramSchema,
                ];
            }
        }

        return $cases;
    }
}
