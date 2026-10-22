<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Helpers\OptionalEnumSchemaGenerator;
use Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService;
use Mcp\Capability\Discovery\SchemaValidator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Runs argument payloads through the SDK's {@see SchemaValidator} — the same check
 * `CallToolHandler` applies before a tool method is ever entered.
 *
 * Every other test in this suite calls the PHP methods directly and therefore exercises
 * inputs that clients can no longer send (`''` for an enum, an out-of-range `maxResults`)
 * while never exercising the rejections clients now get. This is where the schema
 * tightening is actually observable, including the breaking parts of it.
 */
#[CoversNothing]
final class InputSchemaValidationTest extends TestCase
{
    /**
     * @param array<string, mixed> $arguments
     * @param list<string> $expectedInMessage
     */
    #[DataProvider('guideSearchArgumentProvider')]
    public function test_guide_search_arguments_are_validated(array $arguments, bool $shouldPass, array $expectedInMessage = []): void
    {
        $errors = $this->validate(GuideService::class, 'search', $arguments);

        if ($shouldPass) {
            $this->assertSame([], $errors, 'Expected the arguments to validate: ' . json_encode($arguments));
            return;
        }

        $this->assertNotSame([], $errors, 'Expected the arguments to be rejected: ' . json_encode($arguments));
        $rendered = json_encode($errors) ?: '';
        foreach ($expectedInMessage as $needle) {
            $this->assertStringContainsString($needle, $rendered);
        }
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: bool, 2?: list<string>}>
     */
    public static function guideSearchArgumentProvider(): array
    {
        return [
            // Omitting an optional enum is the only way to ask for "no filter".
            'no arguments at all' => [[], true],
            'a valid enum value' => [['category' => 'archetypes'], true],
            'maxResults at the lower bound' => [['maxResults' => 1], true],
            'maxResults at the upper bound' => [['maxResults' => 50], true],

            // BREAKING: the empty-string sentinel used to mean "no filter". Clients must now
            // omit the argument. Paired plugin PR depends on this.
            'empty string for an optional enum' => [['category' => ''], false, ['category', 'enum']],
            // BREAKING (constitution § Contracts, #28): optional enums are published as strings only, so an
            // explicit null is rejected rather than read as "no filter".
            'explicit null for an optional enum' => [['category' => null], false, ['category']],
            'an unknown enum value' => [['category' => 'nonsense'], false, ['category']],

            'a taskType hint' => [['taskType' => 'review'], true],
            // BREAKING: taskType is published as a plain string (a nullable type is not portable
            // to single-`type` schema dialects), so an explicit null is rejected; omit it instead.
            'explicit null for taskType' => [['taskType' => null], false, ['taskType']],

            // BREAKING: out-of-range numbers used to be silently clamped in-method; they are
            // now rejected at the protocol layer, so the in-method clamp is a belt-and-braces
            // guard for non-validating callers rather than the primary defence.
            'maxResults below the minimum' => [['maxResults' => 0], false, ['maxResults']],
            'maxResults above the maximum' => [['maxResults' => 999], false, ['maxResults']],
            'snippetChars below the minimum' => [['snippetChars' => 10], false, ['snippetChars']],
            'topCandidates below the minimum' => [['topCandidates' => 0], false, ['topCandidates']],

            // additionalProperties: false
            'an unknown argument' => [['nosuchparam' => 'x'], false, ['nosuchparam']],
        ];
    }

    public function test_in_method_clamping_still_protects_non_validating_callers(): void
    {
        // The schema bounds are the primary defence, but a stdio client that skips validation
        // must not be able to force an unbounded result set.
        $service = new GuideService(new \Psr\Log\NullLogger());
        $result = $service->search('archetype', maxResults: 999, topCandidates: 500);

        $this->assertLessThanOrEqual(50, count($result['items']));
    }

    /**
     * @param array<string, mixed> $arguments
     * @return list<array<string, mixed>>
     */
    private function validate(string $class, string $method, array $arguments): array
    {
        // The schema the server publishes, so the payloads are checked against what clients see.
        $schema = (new OptionalEnumSchemaGenerator())->generate(new ReflectionMethod($class, $method));

        // Mirror CallToolHandler: arguments are always a JSON object, but an empty one
        // decodes to `[]`, which the validator (SDK >= 0.9) no longer accepts as an object.
        return (new SchemaValidator())->validateAgainstJsonSchema([] === $arguments ? new \stdClass() : $arguments, $schema);
    }
}
