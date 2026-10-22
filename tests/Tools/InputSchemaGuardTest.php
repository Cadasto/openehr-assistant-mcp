<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Helpers\OptionalEnumSchemaGenerator;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * CI guard (REQ-N9): reflects every `#[McpTool]` method through the server's production
 * {@see OptionalEnumSchemaGenerator} (the generator `public/index.php` publishes input
 * schemas with) and asserts two wire-visible invariants that are easy to silently regress
 * when adding a parameter:
 *
 * - The published input schema is closed (`additionalProperties: false`) at the
 *   method/object level, so clients can't silently pass unknown arguments.
 * - Optional (`?string $x = null`) parameters constrained by `#[Schema(enum: [...])]`
 *   are published as plain string enums: `type: "string"`, no `null` in `enum`, no
 *   `default`, and not `required`. Clients that decode `enum` as a list of strings reject
 *   the whole `tools/list` when one holds `null` (issue #28), so omitting the argument is
 *   the only way to ask for "no filter".
 */
#[CoversNothing]
final class InputSchemaGuardTest extends TestCase
{
    /**
     * The optional enum parameters the raw SDK generator renders as nullable today. The
     * provider below must find at least these, or the guard is checking nothing.
     */
    private const array KNOWN_OPTIONAL_ENUM_PARAMETERS = [
        'Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService::get($component)',
        'Cadasto\OpenEHR\MCP\Assistant\Tools\ExamplesService::search($kind)',
        'Cadasto\OpenEHR\MCP\Assistant\Tools\ExamplesService::get($kind)',
        'Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService::search($category)',
        'Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService::get($category)',
    ];

    private static ?SchemaGenerator $rawGenerator = null;

    private static ?OptionalEnumSchemaGenerator $publishedGenerator = null;

    #[DataProvider('mcpToolMethodProvider')]
    public function test_input_schema_declares_additional_properties_false(string $class, string $method): void
    {
        $schema = self::publishedGenerator()->generate(new ReflectionMethod($class, $method));

        $this->assertArrayHasKey('additionalProperties', $schema, sprintf('%s::%s input schema must declare additionalProperties', $class, $method));
        $this->assertFalse($schema['additionalProperties'], sprintf('%s::%s input schema must set additionalProperties: false', $class, $method));
    }

    #[DataProvider('optionalEnumParameterProvider')]
    public function test_optional_enum_parameters_publish_strings_only(string $class, string $method, string $param): void
    {
        $label = sprintf('%s::%s parameter "%s"', $class, $method, $param);
        $schema = self::publishedGenerator()->generate(new ReflectionMethod($class, $method));

        $this->assertIsArray($schema['properties'], sprintf('%s: published schema has no properties', $label));
        $this->assertArrayHasKey($param, $schema['properties'], sprintf('%s: missing from the published schema', $label));
        $property = $schema['properties'][$param];
        $this->assertIsArray($property);

        $this->assertArrayHasKey('enum', $property, sprintf('%s: lost its enum', $label));
        $this->assertIsArray($property['enum']);
        $this->assertNotContains(null, $property['enum'], sprintf('%s: publishes null in enum, which clients that decode enum as strings reject (#28)', $label));
        $this->assertContainsOnlyString($property['enum'], sprintf('%s: enum must hold strings only', $label));
        $this->assertSame('string', $property['type'] ?? null, sprintf('%s: an optional enum must publish type "string", not a nullable type', $label));
        $this->assertArrayNotHasKey('default', $property, sprintf('%s: an optional enum must publish no default', $label));
        $this->assertNotContains($param, $schema['required'] ?? [], sprintf('%s: an optional enum must not be required', $label));
    }

    public function test_optional_enum_provider_finds_the_known_parameters(): void
    {
        $found = array_keys(self::optionalEnumParameterProvider());

        $this->assertNotSame([], $found, 'The raw SDK generator yields no optional enum parameter, so the strings-only guard checks nothing');
        foreach (self::KNOWN_OPTIONAL_ENUM_PARAMETERS as $known) {
            $this->assertContains($known, $found, sprintf('%s is no longer selected as an optional enum parameter', $known));
        }
    }

    public function test_the_server_publishes_with_the_production_generator(): void
    {
        // The guards above read the production generator, so they only hold for the wire if
        // the entrypoint really hands it to the builder.
        $entrypoint = file_get_contents(__DIR__ . '/../../public/index.php');
        $this->assertIsString($entrypoint);

        $this->assertMatchesRegularExpression(
            '/->setSchemaGenerator\(\s*new OptionalEnumSchemaGenerator\(\)\s*\)/',
            $entrypoint,
            'public/index.php must publish input schemas with OptionalEnumSchemaGenerator, or optional enums reach clients with null in enum (#28)',
        );
    }

    private static function rawGenerator(): SchemaGenerator
    {
        return self::$rawGenerator ??= new SchemaGenerator(new DocBlockParser());
    }

    private static function publishedGenerator(): OptionalEnumSchemaGenerator
    {
        return self::$publishedGenerator ??= new OptionalEnumSchemaGenerator();
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
     * Selects the cases from the RAW SDK generator, not the production one: an optional
     * parameter that carries an `enum` and that the SDK renders as nullable (a `null` in
     * `type`, in `enum`, or as the `default`). The raw generator keeps rendering
     * `?string $x = null` that way, so this provider stays non-empty however well the
     * production generator does its job.
     *
     * @return array<string, array{0: class-string, 1: string, 2: string}>
     */
    public static function optionalEnumParameterProvider(): array
    {
        $cases = [];
        foreach (self::mcpToolMethodProvider() as [$class, $method]) {
            $schema = self::rawGenerator()->generate(new ReflectionMethod($class, $method));
            $required = $schema['required'] ?? [];
            $properties = is_array($schema['properties']) ? $schema['properties'] : [];
            foreach ($properties as $paramName => $paramSchema) {
                $paramName = (string) $paramName;
                if (!is_array($paramSchema) || !isset($paramSchema['enum']) || in_array($paramName, $required, true)) {
                    continue;
                }
                $type = $paramSchema['type'] ?? null;
                $nullable = (is_array($type) && in_array('null', $type, true))
                    || (is_array($paramSchema['enum']) && in_array(null, $paramSchema['enum'], true))
                    || (array_key_exists('default', $paramSchema) && $paramSchema['default'] === null);
                if (!$nullable) {
                    continue;
                }
                $cases[sprintf('%s::%s($%s)', $class, $method, $paramName)] = [$class, $method, $paramName];
            }
        }

        return $cases;
    }
}
