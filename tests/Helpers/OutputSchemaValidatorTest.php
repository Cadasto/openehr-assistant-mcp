<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Mutation-style tests for the conformance harness itself: every violation below must be
 * caught, or the eight tools guarded by OutputSchemaConformanceTest are only apparently
 * validated.
 */
#[CoversClass(OutputSchemaValidator::class)]
final class OutputSchemaValidatorTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function envelopeSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'resourceUri', 'score'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'resourceUri' => ['type' => 'string', 'format' => 'uri'],
                            'score' => ['type' => 'integer'],
                            'exact' => ['type' => 'boolean'],
                            'ratio' => ['type' => 'number'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function validEnvelope(): array
    {
        return [
            'items' => [[
                'name' => 'blood_pressure',
                'resourceUri' => 'openehr://examples/archetypes/blood_pressure',
                'score' => 7,
                'exact' => true,
                'ratio' => 0.5,
            ]],
            'total' => 3,
        ];
    }

    public function test_a_conforming_payload_passes(): void
    {
        OutputSchemaValidator::assertValid(self::validEnvelope(), self::envelopeSchema());
        $this->expectNotToPerformAssertions();
    }

    /**
     * @return array<string, array{0: callable(array<string, mixed>): array<string, mixed>, 1: string}>
     */
    public static function violationProvider(): array
    {
        return [
            'missing top-level required key' => [
                static function (array $p): array {
                    unset($p['total']);
                    return $p;
                },
                "missing required 'total'",
            ],
            'unexpected top-level property' => [
                static function (array $p): array {
                    $p['unexpected'] = 1;
                    return $p;
                },
                "unexpected property 'unexpected'",
            ],
            'missing required key inside items' => [
                static function (array $p): array {
                    unset($p['items'][0]['name']);
                    return $p;
                },
                "missing required 'name'",
            ],
            'unexpected property inside items' => [
                static function (array $p): array {
                    $p['items'][0]['sneaky'] = 'x';
                    return $p;
                },
                "unexpected property 'sneaky'",
            ],
            'wrong nested scalar type' => [
                static function (array $p): array {
                    $p['items'][0]['score'] = '7';
                    return $p;
                },
                'expected integer',
            ],
            'null where string declared' => [
                static function (array $p): array {
                    $p['items'][0]['name'] = null;
                    return $p;
                },
                'expected string',
            ],
            'object where array declared' => [
                static function (array $p): array {
                    $p['items'] = ['a' => 1];
                    return $p;
                },
                'expected array',
            ],
            'list where object declared' => [
                static function (array $p): array {
                    $p['items'][0] = ['positional'];
                    return $p;
                },
                'expected object',
            ],
            'malformed uri format' => [
                static function (array $p): array {
                    $p['items'][0]['resourceUri'] = 'not-a-uri';
                    return $p;
                },
                'expected uri',
            ],
            // Regression: `minimum` was previously ignored, leaving the `minimum: 0` on
            // every `total` field unchecked.
            'integer below minimum' => [
                static function (array $p): array {
                    $p['total'] = -1;
                    return $p;
                },
                'below minimum',
            ],
            // Regression: `boolean` was previously ignored, so any value passed.
            'wrong boolean type' => [
                static function (array $p): array {
                    $p['items'][0]['exact'] = 'true';
                    return $p;
                },
                'expected boolean',
            ],
            'wrong number type' => [
                static function (array $p): array {
                    $p['items'][0]['ratio'] = 'half';
                    return $p;
                },
                'expected number',
            ],
        ];
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $mutate
     */
    #[DataProvider('violationProvider')]
    public function test_violation_is_caught(callable $mutate, string $expectedMessage): void
    {
        $payload = $mutate(self::validEnvelope());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($expectedMessage, '/') . '/');
        OutputSchemaValidator::assertValid($payload, self::envelopeSchema());
    }

    public function test_an_integer_satisfies_a_number_declaration(): void
    {
        $payload = self::validEnvelope();
        $payload['items'][0]['ratio'] = 2;

        OutputSchemaValidator::assertValid($payload, self::envelopeSchema());
        $this->expectNotToPerformAssertions();
    }

    public function test_an_empty_array_is_accepted_where_an_object_is_declared(): void
    {
        // `type_specification_get` declares `constants`/`properties`/`functions` as objects;
        // a BMM document with `"constants": {}` decodes to an empty PHP array and must not
        // be reported as "expected object".
        OutputSchemaValidator::assertValid([], ['type' => 'object']);
        $this->expectNotToPerformAssertions();
    }

    public function test_an_unimplemented_keyword_fails_loudly(): void
    {
        // Guard against the harness silently weakening: a schema constraint this validator
        // cannot check must break the build rather than pass unverified.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/does not implement/');
        OutputSchemaValidator::assertValid('x', ['type' => 'string', 'pattern' => '^x$']);
    }

    public function test_a_non_schema_items_declaration_fails_loudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/`items` must be a schema object/');
        OutputSchemaValidator::assertValid([1], ['type' => 'array', 'items' => true]);
    }

    public function test_an_unsupported_type_fails_loudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/unsupported schema type/');
        OutputSchemaValidator::assertValid('x', ['type' => 'timestamp']);
    }

    public function test_enum_is_enforced(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not one of the allowed values/');
        OutputSchemaValidator::assertValid('archetypes', ['type' => 'string', 'enum' => ['aql', 'flat']]);
    }

    public function test_maximum_is_enforced(): void
    {
        // `minimum` had a regression test; `maximum`'s branch was never exercised.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is above maximum/');
        OutputSchemaValidator::assertValid(51, ['type' => 'integer', 'maximum' => 50]);
    }

    public function test_an_absent_optional_property_is_skipped(): void
    {
        // The complement of the required check: a property that is declared but not present
        // must not be validated (or the minimal CKM row would fail).
        OutputSchemaValidator::assertValid(
            ['cid' => '1.2.3'],
            [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['cid'],
                'properties' => ['cid' => ['type' => 'string'], 'status' => ['type' => 'string']],
            ],
        );
        $this->expectNotToPerformAssertions();
    }

    public function test_an_unimplemented_format_value_fails_loudly(): void
    {
        // `format` is allow-listed as a keyword, but only `uri` is implemented. Accepting
        // `date-time` and then checking nothing is the same silent-ignore the class exists
        // to prevent — plausible for `creationTime`, which is a bare string today.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/does not implement/');
        OutputSchemaValidator::assertValid('2026-07-28', ['type' => 'string', 'format' => 'date-time']);
    }

    public function test_a_schema_form_additional_properties_fails_loudly(): void
    {
        // `additionalProperties: {type: string}` used to be read as fully permissive.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/`additionalProperties` must be a boolean/');
        OutputSchemaValidator::assertValid(
            ['a' => 'x'],
            ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
        );
    }

    public function test_object_keywords_without_a_type_fail_loudly(): void
    {
        // Legal JSON Schema, and an easy omission in a hand-written nested `items`: without
        // `type: object` every one of these constraints used to be skipped in silence.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/declares no `type`/');
        OutputSchemaValidator::assertValid(
            ['unexpected' => 'x'],
            ['required' => ['cid'], 'properties' => ['cid' => ['type' => 'string']], 'additionalProperties' => false],
        );
    }

    public function test_a_malformed_numeric_bound_fails_loudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/`minimum` must be numeric/');
        OutputSchemaValidator::assertValid(5, ['type' => 'integer', 'minimum' => '3']);
    }

    public function test_a_malformed_enum_fails_loudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/`enum` must be a non-empty array/');
        OutputSchemaValidator::assertValid('x', ['type' => 'string', 'enum' => []]);
    }
}
