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
 * CI guard (constitution § Contracts): reflects every `#[McpTool]` method through the server's production
 * {@see OptionalEnumSchemaGenerator} (the generator `public/index.php` publishes input
 * schemas with) and asserts wire-visible invariants that are easy to silently regress
 * when adding a parameter:
 *
 * - The published input schema is closed (`additionalProperties: false`) at the
 *   method/object level, so clients can't silently pass unknown arguments.
 * - Optional (`?string $x = null`) parameters constrained by `#[Schema(enum: [...])]`
 *   are published as plain string enums: `type: "string"`, no `null` in `enum`, no
 *   `default`, and not `required`. Clients that decode `enum` as a list of strings reject
 *   the whole `tools/list` when one holds `null` (issue #28), so omitting the argument is
 *   the only way to ask for "no filter". Every other key, such as `description`, is
 *   published as the SDK renders it.
 * - No published `enum` holds `null`, at any depth, whether its parameter is required or
 *   optional.
 * - `public/index.php` hands this generator to the builder, and sets no other after it.
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

    private const string WIRING_PATTERN = '/->setSchemaGenerator\(\s*new OptionalEnumSchemaGenerator\(\)\s*\)/';

    /** The discovery call in public/index.php; group 1 is its list of directories. */
    private const string DISCOVERY_PATTERN = '/->setDiscovery\(\s*APP_DIR\s*,\s*\[([^\]]*)\]/';

    /** The composer.json PSR-4 mapping for application classes. */
    private const string PSR4_PREFIX = 'Cadasto\\OpenEHR\\MCP\\Assistant\\';

    private const string PSR4_DIRECTORY = 'src';

    /** JSON Schema keywords whose keys name subschemas. */
    private const array SUBSCHEMA_MAPS = ['properties', 'patternProperties', '$defs', 'definitions'];

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

        $rawSchema = self::rawGenerator()->generate(new ReflectionMethod($class, $method));
        $this->assertIsArray($rawSchema['properties']);
        $rawProperty = $rawSchema['properties'][$param] ?? null;
        $this->assertIsArray($rawProperty);
        $this->assertSame(
            self::withoutRewrittenKeys($rawProperty),
            self::withoutRewrittenKeys($property),
            sprintf('%s: the rewrite may change only type, enum and default; every other key, such as the description that tells clients to omit the argument, must be published as the SDK renders it', $label),
        );
    }

    public function test_no_published_enum_holds_null(): void
    {
        // Every enum at any depth, required parameter or optional: issue #28's symptom is a
        // null in any enum, such as one under an array parameter's `items`.
        $enumParameters = [];
        $enumsWithNull = [];
        foreach (self::mcpToolMethodProvider() as [$class, $method]) {
            $schema = self::publishedGenerator()->generate(new ReflectionMethod($class, $method));
            $properties = is_array($schema['properties']) ? $schema['properties'] : [];
            foreach ($properties as $param => $property) {
                if (is_array($property) && array_key_exists('enum', $property)) {
                    $enumParameters[] = sprintf('%s::%s($%s)', $class, $method, $param);
                }
            }
            foreach (self::enumsIn($schema, '#') as $pointer => $enum) {
                if (in_array(null, $enum, true)) {
                    $enumsWithNull[] = sprintf('%s::%s %s', $class, $method, $pointer);
                }
            }
        }

        foreach (self::KNOWN_OPTIONAL_ENUM_PARAMETERS as $known) {
            $this->assertContains($known, $enumParameters, sprintf('%s publishes no enum, so this check no longer sees it', $known));
        }
        $this->assertSame([], $enumsWithNull, 'These published enums hold null, which clients that decode enum as a list of strings reject, dropping every tool (#28)');
    }

    public function test_enum_walk_reaches_nested_enums(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'kind' => ['type' => 'string', 'enum' => ['a', 'b']],
                'tags' => ['type' => 'array', 'items' => ['type' => ['null', 'string'], 'enum' => ['x', null]]],
                'enum' => ['type' => 'string', 'enum' => ['y', null]],
            ],
        ];

        $this->assertSame([
            '#/properties/kind' => ['a', 'b'],
            '#/properties/tags/items' => ['x', null],
            '#/properties/enum' => ['y', null],
        ], self::enumsIn($schema, '#'));
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
        $liveCode = self::liveCode($entrypoint);

        $this->assertMatchesRegularExpression(
            self::WIRING_PATTERN,
            $liveCode,
            'public/index.php must publish input schemas with OptionalEnumSchemaGenerator, or optional enums reach clients with null in enum (#28)',
        );
        $this->assertSame(
            1,
            preg_match_all('/->setSchemaGenerator\(/', $liveCode),
            'public/index.php must set the schema generator once: the SDK builder keeps the last one it is given, so a second call would replace OptionalEnumSchemaGenerator',
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function wiringOutsideLiveCodeProvider(): array
    {
        return [
            'line comment' => ["<?php\n\$builder\n    // ->setSchemaGenerator(new OptionalEnumSchemaGenerator())\n    ->build();\n"],
            'hash comment' => ["<?php\n\$builder\n    # ->setSchemaGenerator(new OptionalEnumSchemaGenerator())\n    ->build();\n"],
            'block comment' => ["<?php\n\$builder\n    /* ->setSchemaGenerator(new OptionalEnumSchemaGenerator()) */\n    ->build();\n"],
            'doc comment' => ["<?php\n/** ->setSchemaGenerator(new OptionalEnumSchemaGenerator()) */\n\$builder->build();\n"],
            'string literal' => ["<?php\n\$note = '->setSchemaGenerator(new OptionalEnumSchemaGenerator())';\n\$builder->build();\n"],
        ];
    }

    #[DataProvider('wiringOutsideLiveCodeProvider')]
    public function test_wiring_check_ignores_a_call_outside_live_code(string $source): void
    {
        $this->assertMatchesRegularExpression(self::WIRING_PATTERN, $source, 'Fixture precondition: the raw text holds the call');

        $this->assertDoesNotMatchRegularExpression(self::WIRING_PATTERN, self::liveCode($source), 'A call that is commented out or quoted does not wire the generator');
    }

    /**
     * Every `enum` keyword in a JSON Schema, at any depth, keyed by its JSON Pointer.
     *
     * The keys of a subschema map such as `properties` are names, not keywords, so a
     * parameter called `enum` is walked into rather than read as the keyword.
     *
     * @return array<string, array<mixed>>
     */
    private static function enumsIn(mixed $schema, string $pointer, bool $keysAreNames = false): array
    {
        if (!is_array($schema)) {
            return [];
        }

        $enums = [];
        foreach ($schema as $key => $child) {
            if (!$keysAreNames && $key === 'enum') {
                if (is_array($child)) {
                    $enums[$pointer] = $child;
                }
                continue;
            }
            $childKeysAreNames = !$keysAreNames && in_array($key, self::SUBSCHEMA_MAPS, true);
            $enums += self::enumsIn($child, $pointer . '/' . $key, $childKeysAreNames);
        }

        return $enums;
    }

    /**
     * The PHP source without its comments and string literals, so that only code that runs
     * can satisfy a pattern.
     */
    private static function liveCode(string $source): string
    {
        $ignored = [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML];
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], $ignored, true)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    /**
     * A property without the keys the production generator may rewrite.
     *
     * @param array<mixed> $property
     * @return array<mixed>
     */
    private static function withoutRewrittenKeys(array $property): array
    {
        return array_diff_key($property, array_flip(['type', 'enum', 'default']));
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
     * Every method the server publishes as a tool, found the way the SDK discovers them: each
     * class under the directories `public/index.php` passes to `setDiscovery()`, searched
     * recursively, that is not abstract, an interface, a trait or an enum. A class carrying
     * `#[McpTool]` publishes its `__invoke`; otherwise each public, non-static method it
     * declares itself and that carries `#[McpTool]` is a tool.
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function mcpToolMethodProvider(): array
    {
        // Discovered, not hardcoded: a new tool is published to clients whether or not it is
        // listed here, so a tool outside src/Tools must not escape these checks either.
        $classes = self::discoveredClasses();
        self::assertNotEmpty($classes, 'No classes discovered under the directories public/index.php passes to setDiscovery()');

        $cases = [];
        foreach ($classes as $class) {
            $reflectionClass = new \ReflectionClass($class);
            if ($reflectionClass->isAbstract() || $reflectionClass->isInterface() || $reflectionClass->isTrait() || $reflectionClass->isEnum()) {
                continue;
            }
            if ($reflectionClass->hasMethod('__invoke') && $reflectionClass->getAttributes(McpTool::class, \ReflectionAttribute::IS_INSTANCEOF) !== []) {
                $cases[$class . '::__invoke'] = [$class, '__invoke'];
                continue;
            }
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                if ($reflectionMethod->getDeclaringClass()->getName() !== $class || $reflectionMethod->isStatic()) {
                    continue;
                }
                if ($reflectionMethod->getAttributes(McpTool::class, \ReflectionAttribute::IS_INSTANCEOF) === []) {
                    continue;
                }
                $cases[$class . '::' . $reflectionMethod->getName()] = [$class, $reflectionMethod->getName()];
            }
        }
        self::assertNotEmpty($cases, 'No #[McpTool] methods discovered; every check fed by this provider would be vacuous');

        return $cases;
    }

    /**
     * The classes under the directories `public/index.php` hands to `setDiscovery()`, searched
     * recursively and mapped to class names through the PSR-4 prefix for `src/`, sorted.
     *
     * @return list<class-string>
     */
    private static function discoveredClasses(): array
    {
        $entrypoint = file_get_contents(__DIR__ . '/../../public/index.php');
        self::assertIsString($entrypoint);
        self::assertSame(
            1,
            preg_match(self::DISCOVERY_PATTERN, self::withoutComments($entrypoint), $call),
            'public/index.php no longer calls ->setDiscovery(APP_DIR, [...]); update this provider so it scans what the server discovers',
        );
        preg_match_all('/[\'"]([^\'"]+)[\'"]/', $call[1], $directories);
        self::assertNotEmpty($directories[1], 'public/index.php passes no directory to setDiscovery()');

        $classes = [];
        foreach ($directories[1] as $directory) {
            $directory = trim($directory, '/');
            self::assertStringStartsWith(
                self::PSR4_DIRECTORY . '/',
                $directory . '/',
                sprintf('setDiscovery() scans "%s", outside the PSR-4 root %s/, so this provider cannot map its files to classes', $directory, self::PSR4_DIRECTORY),
            );
            $root = APP_DIR . '/' . $directory;
            self::assertDirectoryExists($root);

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $relative = substr($file->getPathname(), strlen(APP_DIR . '/' . self::PSR4_DIRECTORY . '/'), -strlen('.php'));
                $class = self::PSR4_PREFIX . str_replace('/', '\\', $relative);
                if (class_exists($class)) {
                    $classes[] = $class;
                }
            }
        }
        $classes = array_values(array_unique($classes));
        sort($classes);

        return $classes;
    }

    /**
     * The PHP source without its comments, so a commented-out call cannot be read as live.
     */
    private static function withoutComments(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
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
