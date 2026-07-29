<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
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
            'no arguments at all' => [[], true],
            'null for a nullable enum' => [['category' => null], true],
            'a valid enum value' => [['category' => 'archetypes'], true],
            'maxResults at the lower bound' => [['maxResults' => 1], true],
            'maxResults at the upper bound' => [['maxResults' => 50], true],

            // BREAKING: the empty-string sentinel used to mean "no filter". Clients must now
            // omit the argument or send null. Paired plugin PR depends on this.
            'empty string for a nullable enum' => [['category' => ''], false, ['category', 'enum']],
            'an unknown enum value' => [['category' => 'nonsense'], false, ['category']],

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
        $schema = (new SchemaGenerator(new DocBlockParser()))->generate(new ReflectionMethod($class, $method));

        return (new SchemaValidator())->validateAgainstJsonSchema($arguments, $schema);
    }
}
