<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Helpers;

use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Capability\Discovery\SchemaGeneratorInterface;

/**
 * Publishes optional enum parameters as plain string enums (REQ-N9, issue #28).
 *
 * The SDK renders `#[Schema(enum: [...])] ?string $x = null` as
 * `{"type": ["null", "string"], "default": null, "enum": [...]}`, and adds `null` to the
 * enum when the attribute lists it. Clients that decode `enum` as a list of strings
 * (JetBrains AI Assistant) reject the whole `tools/list` on that `null`, so no tool loads.
 *
 * This wrapper removes `null` from such a parameter's `enum` and `type` and drops its
 * `null` default. Omitting the argument still means "no filter"; an explicit `null` is
 * now rejected by the SDK's input validation. Required parameters, parameters without an
 * enum and every other schema key pass through unchanged.
 */
final readonly class OptionalEnumSchemaGenerator implements SchemaGeneratorInterface
{
    public function __construct(
        private SchemaGeneratorInterface $inner = new SchemaGenerator(new DocBlockParser()),
    ) {
    }

    #[\Override]
    public function generate(\Reflector $reflection): array
    {
        $schema = $this->inner->generate($reflection);

        $properties = self::propertiesOf($schema);
        if ($properties === null) {
            return $schema;
        }

        $required = $schema['required'] ?? [];
        foreach ($properties as $name => $property) {
            if (is_array($property) && !in_array($name, $required, true)) {
                $properties[$name] = self::withoutNull($property);
            }
        }
        $schema['properties'] = $properties;

        return $schema;
    }

    #[\Override]
    public function generateOutputSchema(\Reflector $reflection): ?array
    {
        return $this->inner->generateOutputSchema($reflection);
    }

    /**
     * The schema's parameter map, or null when it has none to rewrite.
     *
     * A method-level `#[Schema(definition: ...)]` is published as written and may carry no
     * `properties`, a schema without parameters carries an empty object, and mcp/sdk 0.9
     * types the key as optional. So the key is read from a plain array here.
     *
     * @param array<mixed> $schema
     * @return array<string, mixed>|null
     */
    private static function propertiesOf(array $schema): ?array
    {
        $properties = $schema['properties'] ?? null;
        if (!is_array($properties)) {
            return null;
        }

        /** @var array<string, mixed> $properties JSON Schema property names are strings. */
        return $properties;
    }

    /**
     * Strips `null` from an optional enum parameter's `enum`, its `type` and its `default`.
     *
     * A single remaining type collapses to a plain string. A parameter without an `enum`
     * is returned as it was, and so is a non-null default.
     *
     * @param array<mixed> $property
     * @return array<mixed>
     */
    private static function withoutNull(array $property): array
    {
        $enum = $property['enum'] ?? null;
        if (!is_array($enum)) {
            return $property;
        }

        $property['enum'] = array_values(array_filter($enum, static fn (mixed $value): bool => $value !== null));

        $type = $property['type'] ?? null;
        if (is_array($type)) {
            $types = array_values(array_filter($type, static fn (mixed $value): bool => $value !== 'null'));
            $property['type'] = count($types) === 1 ? $types[0] : $types;
        }

        if (array_key_exists('default', $property) && $property['default'] === null) {
            unset($property['default']);
        }

        return $property;
    }
}
