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

        // Object keywords used to be honoured only when `type: 'object'` was literally
        // present. A sub-schema written as `['required' => [...], 'properties' => [...]]` —
        // legal JSON Schema, and an easy omission when hand-writing a nested `items` — fell
        // through to the scalar path, which returns immediately for a null type. Every
        // constraint was then skipped and the payload passed unchecked.
        $objectKeywords = array_intersect(['properties', 'required', 'additionalProperties'], array_keys($schema));
        if ($type === null && $objectKeywords !== []) {
            throw new InvalidArgumentException(sprintf(
                '%s: schema uses %s but declares no `type` — add `type: "object"` so the constraint is actually enforced.',
                $path,
                implode('/', $objectKeywords),
            ));
        }

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

        // Only the boolean form is implemented. `additionalProperties: {type: string}` is
        // legal JSON Schema but used to be treated as fully permissive — a constraint the
        // author wrote and believed was being checked.
        $additional = $schema['additionalProperties'] ?? true;
        if (!is_bool($additional)) {
            throw new InvalidArgumentException(sprintf(
                '%s: `additionalProperties` must be a boolean; %s does not implement the schema form.',
                $path,
                self::class,
            ));
        }

        if ($additional === false) {
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

        // A non-numeric bound is a malformed schema, not an absent constraint. Skipping it
        // silently would leave the author believing a bound they wrote is being enforced.
        $minimum = $schema['minimum'] ?? null;
        if ($minimum !== null) {
            if (!is_int($minimum) && !is_float($minimum)) {
                throw new InvalidArgumentException(sprintf('%s: `minimum` must be numeric, %s given', $path, get_debug_type($minimum)));
            }
            if ($data < $minimum) {
                throw new InvalidArgumentException(sprintf('%s: %s is below minimum %s', $path, (string) $data, (string) $minimum));
            }
        }

        $maximum = $schema['maximum'] ?? null;
        if ($maximum !== null) {
            if (!is_int($maximum) && !is_float($maximum)) {
                throw new InvalidArgumentException(sprintf('%s: `maximum` must be numeric, %s given', $path, get_debug_type($maximum)));
            }
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
        if ($enum === null) {
            return;
        }
        if (!is_array($enum) || $enum === []) {
            throw new InvalidArgumentException(sprintf('%s: `enum` must be a non-empty array, %s given', $path, get_debug_type($enum)));
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
        $format = $schema['format'] ?? null;
        if ($format === null) {
            return;
        }

        // The keyword allowlist admits `format`, but support is per *value*. Accepting an
        // unimplemented value and then checking nothing is the same silent-ignore this
        // class exists to prevent, one level down — so reject the value, not just the keyword.
        if ($format !== 'uri') {
            throw new InvalidArgumentException(sprintf(
                '%s: schema uses format "%s", which %s does not implement — add support for it rather than leaving it unchecked.',
                $path,
                is_string($format) ? $format : get_debug_type($format),
                self::class,
            ));
        }

        if (!is_string($data)) {
            return;
        }

        if ($data === '' || !preg_match('#^[a-z][a-z0-9+.-]*:#i', $data)) {
            throw new InvalidArgumentException("$path: expected uri");
        }
    }
}
