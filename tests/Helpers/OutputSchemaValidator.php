<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Helpers;

use InvalidArgumentException;

/**
 * Minimal JSON Schema checker for the `outputSchema` declarations on this server's tools.
 *
 * Deliberately a small subset rather than a dependency: it only has to understand the
 * keywords the tools actually publish, and it validates *real* tool return values against
 * the *real* `#[McpTool]` attribute so the two cannot drift.
 *
 * Supported keywords: `type` (object, array, string, integer, number, boolean),
 * `properties`, `required`, `additionalProperties`, `items`, `minimum`, `maximum`,
 * `enum`, `format: uri`, and `description` (ignored — documentation only).
 *
 * Any other keyword raises {@see InvalidArgumentException}. That is the point: silently
 * ignoring an unrecognised keyword would weaken every conformance test that relies on
 * this class the moment a schema gains a constraint it does not implement.
 */
final class OutputSchemaValidator
{
    /**
     * Keywords that carry no validation semantics here.
     */
    private const array ANNOTATION_KEYWORDS = ['description', 'title', 'default', 'examples'];

    private const array SUPPORTED_KEYWORDS = [
        'type',
        'properties',
        'required',
        'additionalProperties',
        'items',
        'minimum',
        'maximum',
        'enum',
        'format',
    ];

    /**
     * @param array<string, mixed> $schema
     */
    public static function assertValid(mixed $data, array $schema, string $path = '$'): void
    {
        self::assertKeywordsSupported($schema, $path);

        $type = $schema['type'] ?? null;
        if ($type === 'object') {
            self::assertObject($data, $schema, $path);
            return;
        }
        if ($type === 'array') {
            self::assertArray($data, $schema, $path);
            return;
        }

        self::assertScalarType($data, $type, $path);
        self::assertRange($data, $schema, $path);
        self::assertEnum($data, $schema, $path);
        self::assertFormat($data, $schema, $path);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private static function assertKeywordsSupported(array $schema, string $path): void
    {
        foreach (array_keys($schema) as $keyword) {
            if (in_array($keyword, self::ANNOTATION_KEYWORDS, true) || in_array($keyword, self::SUPPORTED_KEYWORDS, true)) {
                continue;
            }

            throw new InvalidArgumentException(sprintf(
                '%s: schema uses keyword "%s", which %s does not implement — add support for it rather than leaving it unchecked.',
                $path,
                (string) $keyword,
                self::class,
            ));
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private static function assertObject(mixed $data, array $schema, string $path): void
    {
        // An empty PHP array is an ambiguous encoding of both `{}` and `[]`; treat it as a
        // valid empty object so a schema-declared object with no entries does not false-fail.
        if (!is_array($data) || (array_is_list($data) && $data !== [])) {
            throw new InvalidArgumentException("$path: expected object");
        }

        /** @var list<string> $required */
        $required = $schema['required'] ?? [];
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException("$path: missing required '$key'");
            }
        }

        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'] ?? [];

        if (($schema['additionalProperties'] ?? true) === false) {
            $allowed = array_keys($properties);
            foreach (array_keys($data) as $key) {
                if (!in_array($key, $allowed, true)) {
                    throw new InvalidArgumentException("$path: unexpected property '$key'");
                }
            }
        }

        foreach ($properties as $key => $propSchema) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            self::assertValid($data[$key], $propSchema, $path . '.' . $key);
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private static function assertArray(mixed $data, array $schema, string $path): void
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw new InvalidArgumentException("$path: expected array");
        }

        $itemSchema = $schema['items'] ?? null;
        if ($itemSchema === null) {
            return;
        }
        if (!is_array($itemSchema)) {
            throw new InvalidArgumentException("$path: `items` must be a schema object");
        }

        foreach ($data as $i => $item) {
            self::assertValid($item, $itemSchema, $path . "[$i]");
        }
    }

    private static function assertScalarType(mixed $data, mixed $type, string $path): void
    {
        if ($type === null) {
            return;
        }

        $ok = match ($type) {
            'string' => is_string($data),
            'integer' => is_int($data),
            // JSON Schema `number` admits integers too.
            'number' => is_int($data) || is_float($data),
            'boolean' => is_bool($data),
            'null' => $data === null,
            default => throw new InvalidArgumentException(sprintf('%s: unsupported schema type "%s"', $path, (string) $type)),
        };

        if (!$ok) {
            throw new InvalidArgumentException(sprintf('%s: expected %s, got %s', $path, (string) $type, get_debug_type($data)));
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private static function assertRange(mixed $data, array $schema, string $path): void
    {
        if (!is_int($data) && !is_float($data)) {
            return;
        }

        $minimum = $schema['minimum'] ?? null;
        if (is_int($minimum) || is_float($minimum)) {
            if ($data < $minimum) {
                throw new InvalidArgumentException(sprintf('%s: %s is below minimum %s', $path, (string) $data, (string) $minimum));
            }
        }

        $maximum = $schema['maximum'] ?? null;
        if (is_int($maximum) || is_float($maximum)) {
            if ($data > $maximum) {
                throw new InvalidArgumentException(sprintf('%s: %s is above maximum %s', $path, (string) $data, (string) $maximum));
            }
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private static function assertEnum(mixed $data, array $schema, string $path): void
    {
        $enum = $schema['enum'] ?? null;
        if (!is_array($enum) || $enum === []) {
            return;
        }

        if (!in_array($data, $enum, true)) {
            throw new InvalidArgumentException(sprintf('%s: %s is not one of the allowed values', $path, var_export($data, true)));
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private static function assertFormat(mixed $data, array $schema, string $path): void
    {
        if (($schema['format'] ?? null) !== 'uri' || !is_string($data)) {
            return;
        }

        if ($data === '' || !preg_match('#^[a-z][a-z0-9+.-]*:#i', $data)) {
            throw new InvalidArgumentException("$path: expected uri");
        }
    }
}
