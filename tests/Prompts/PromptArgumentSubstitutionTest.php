<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\AbstractPrompt;
use Mcp\Exception\PromptGetException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Wire-style test: every parameterized prompt substitutes its {{placeholders}} and
 * every placeholder in a prompt markdown file is covered by that prompt's __invoke
 * parameters (i.e. nothing typo'd or missing from the reflection-discovered schema).
 */
#[CoversNothing]
final class PromptArgumentSubstitutionTest extends TestCase
{
    private const string PROMPTS_NAMESPACE = 'Cadasto\\OpenEHR\\MCP\\Assistant\\Prompts\\';

    #[DataProvider('parameterizedPromptProvider')]
    public function test_prompt_substitutes_all_placeholders_and_leaves_none_unresolved(string $className): void
    {
        $rc = new ReflectionClass($className);
        $params = $rc->getMethod('__invoke')->getParameters();
        $this->assertNotEmpty($params, sprintf('%s should declare __invoke parameters', $className));

        $args = [];
        foreach ($params as $param) {
            $args[$param->getName()] = 'value-for-' . $param->getName();
        }

        /** @var callable $prompt */
        $prompt = $rc->newInstance();
        $messages = $prompt(...$args);

        $combined = implode("\n", array_map(
            static fn ($message): string => $message->content->text,
            $messages,
        ));

        foreach ($args as $name => $value) {
            $this->assertStringContainsString($value, $combined, sprintf('%s: %s not substituted', $className, $name));
        }

        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);
    }

    /**
     * Pins the `requiredKeys` argument of every `loadPromptMessages()` call against the
     * `__invoke()` signature, in both directions: every parameter without a default must
     * be enforced as required, and every parameter *with* a default must stay optional.
     * A key dropped from (or typo'd in) a `requiredKeys` list fails here.
     */
    #[DataProvider('parameterizedPromptProvider')]
    public function test_required_keys_match_the_invoke_signature(string $className): void
    {
        $rc = new ReflectionClass($className);
        $params = $rc->getMethod('__invoke')->getParameters();
        $this->assertNotEmpty($params);

        $baseline = [];
        foreach ($params as $param) {
            $baseline[$param->getName()] = 'value-for-' . $param->getName();
        }

        $requiredSeen = 0;
        foreach ($params as $param) {
            $name = $param->getName();
            /** @var callable $prompt */
            $prompt = $rc->newInstance();

            if (!$param->isDefaultValueAvailable()) {
                ++$requiredSeen;

                foreach ([null, '', "  \t "] as $blank) {
                    try {
                        $prompt(...[...$baseline, $name => $blank]);
                        $this->fail(sprintf('%s: expected %s to be enforced as required', $className, $name));
                    } catch (PromptGetException $e) {
                        $this->assertStringContainsString(
                            $name,
                            $e->getMessage(),
                            sprintf('%s: error for %s should name the argument', $className, $name),
                        );
                    }
                }

                continue;
            }

            // Optional: a blank value must render as an empty placeholder, not throw.
            $messages = $prompt(...[...$baseline, $name => '']);
            $this->assertNotEmpty($messages, sprintf('%s: %s should be optional', $className, $name));
        }

        $this->assertGreaterThan(0, $requiredSeen, sprintf('%s should declare at least one required argument', $className));
    }

    #[DataProvider('parameterizedPromptProvider')]
    public function test_prompt_renders_with_only_its_required_arguments(string $className): void
    {
        // The normal client case: optional arguments omitted entirely. Their placeholders
        // must resolve to an empty string rather than tripping the unresolved guard.
        $rc = new ReflectionClass($className);
        $params = $rc->getMethod('__invoke')->getParameters();

        $args = [];
        foreach ($params as $param) {
            if ($param->isDefaultValueAvailable()) {
                continue;
            }
            $args[$param->getName()] = 'value-for-' . $param->getName();
        }

        /** @var callable $prompt */
        $prompt = $rc->newInstance();
        $messages = $prompt(...$args);

        $combined = implode("\n", array_map(
            static fn ($message): string => $message->content->text,
            $messages,
        ));

        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);
        foreach ($args as $value) {
            $this->assertStringContainsString($value, $combined);
        }
    }

    #[DataProvider('parameterizedPromptProvider')]
    public function test_non_string_argument_is_rejected_rather_than_stringified(string $className): void
    {
        $rc = new ReflectionClass($className);
        $params = $rc->getMethod('__invoke')->getParameters();

        $args = [];
        foreach ($params as $param) {
            $args[$param->getName()] = 'value-for-' . $param->getName();
        }
        $first = $params[0]->getName();
        $args[$first] = ['an', 'array'];

        /** @var callable $prompt */
        $prompt = $rc->newInstance();

        $this->expectException(PromptGetException::class);
        $this->expectExceptionMessage(sprintf('Prompt argument "%s" must be a string, array given.', $first));
        $prompt(...$args);
    }

    #[DataProvider('parameterizedPromptProvider')]
    public function test_invoke_parameters_are_typed_mixed_so_the_sdk_preserves_client_values(string $className): void
    {
        // Guard for the two SDK behaviours documented in AbstractPrompt::normaliseArguments:
        // a `string` type would coerce an array to "Array", and a non-nullable type would
        // make an omitted argument abort before our own error messages can run.
        $rc = new ReflectionClass($className);

        foreach ($rc->getMethod('__invoke')->getParameters() as $param) {
            $type = $param->getType();
            $this->assertInstanceOf(ReflectionNamedType::class, $type, sprintf('%s::%s', $className, $param->getName()));
            $this->assertSame(
                'mixed',
                $type->getName(),
                sprintf('%s::$%s must stay `mixed`; see AbstractPrompt::normaliseArguments()', $className, $param->getName()),
            );
        }
    }

    public function test_every_markdown_placeholder_is_covered_by_invoke_parameters(): void
    {
        $promptsDir = __DIR__ . '/../../resources/prompts';

        foreach (self::parameterizedPromptFiles() as $file => $className) {
            $content = file_get_contents($promptsDir . '/' . $file);
            $this->assertIsString($content);

            preg_match_all('/\{\{([a-z0-9_]+)\}\}/', $content, $matches);
            $placeholders = array_unique($matches[1]);
            $this->assertNotEmpty($placeholders, sprintf('%s expected to contain placeholders', $file));

            $rc = new ReflectionClass($className);
            $paramNames = array_map(
                static fn ($param): string => $param->getName(),
                $rc->getMethod('__invoke')->getParameters(),
            );

            foreach ($placeholders as $placeholder) {
                $this->assertContains(
                    $placeholder,
                    $paramNames,
                    sprintf('%s: {{%s}} has no matching __invoke parameter in %s', $file, $placeholder, $className),
                );
            }
        }
    }

    public function test_explorer_prompts_remain_parameterless(): void
    {
        $explorerClasses = [
            self::PROMPTS_NAMESPACE . 'GuideExplorer',
            self::PROMPTS_NAMESPACE . 'CkmExplorer',
            self::PROMPTS_NAMESPACE . 'TerminologyExplorer',
            self::PROMPTS_NAMESPACE . 'TypeSpecificationExplorer',
        ];

        foreach ($explorerClasses as $className) {
            $rc = new ReflectionClass($className);
            $params = $rc->getMethod('__invoke')->getParameters();
            $this->assertSame([], $params, sprintf('%s should remain parameterless', $className));

            $returnType = $rc->getMethod('__invoke')->getReturnType();
            $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
            $this->assertSame('array', $returnType->getName());
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function parameterizedPromptProvider(): array
    {
        $cases = [];
        foreach (self::parameterizedPromptFiles() as $file => $className) {
            $cases[$file] = [$className];
        }

        return $cases;
    }

    /**
     * @return array<string, class-string<AbstractPrompt>>
     */
    private static function parameterizedPromptFiles(): array
    {
        $promptsDir = __DIR__ . '/../../resources/prompts';
        $files = glob($promptsDir . '/*.md') ?: [];

        $map = [];
        foreach ($files as $path) {
            $file = basename($path);
            $content = file_get_contents($path);
            if ($content === false || !str_contains($content, '{{')) {
                continue;
            }

            $className = self::PROMPTS_NAMESPACE . self::studlyCase(substr($file, 0, -3));
            $map[$file] = $className;
        }

        return $map;
    }

    private static function studlyCase(string $snakeCase): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $snakeCase)));
    }
}
