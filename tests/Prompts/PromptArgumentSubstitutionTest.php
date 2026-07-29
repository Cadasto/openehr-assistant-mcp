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

    /**
     * One legal token per argument constrained to a closed vocabulary. The generic
     * reflection-driven tests below feed `value-for-<name>` to every argument, which
     * `AbstractPrompt::applyVocabularies()` rejects by design.
     *
     * The samples deliberately pick the value that triggers *no* conditional requirement
     * (`design`, not `review`), so a prompt still renders when only its required arguments
     * are supplied. If a new vocabulary is declared without being added here, the generic
     * tests fail with the "must be one of" message naming the argument.
     */
    private const array VOCABULARY_SAMPLES = [
        'task_type' => 'design',
        'format_variant' => 'flat',
    ];

    /** Any `{{…}}`-shaped token, including ones the substituter would not replace. */
    private const string ANY_PLACEHOLDER = '/\{\{[^{}]*\}\}/';

    private static function sampleValue(string $parameterName): string
    {
        return self::VOCABULARY_SAMPLES[$parameterName] ?? 'value-for-' . $parameterName;
    }

    #[DataProvider('parameterizedPromptProvider')]
    public function test_prompt_substitutes_all_placeholders_and_leaves_none_unresolved(string $className): void
    {
        $rc = new ReflectionClass($className);
        $params = $rc->getMethod('__invoke')->getParameters();
        $this->assertNotEmpty($params, sprintf('%s should declare __invoke parameters', $className));

        $args = [];
        foreach ($params as $param) {
            $args[$param->getName()] = self::sampleValue($param->getName());
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

        // Asserted with the broad pattern, not the substituter's strict one: matching only
        // `[a-z0-9_]+` made a leaked `{{Audience}}` or `{{adl-text}}` invisible to this
        // guard as well as to production, so the leak shipped with CI green.
        $this->assertDoesNotMatchRegularExpression(self::ANY_PLACEHOLDER, $combined);
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
            $baseline[$param->getName()] = self::sampleValue($param->getName());
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
            $args[$param->getName()] = self::sampleValue($param->getName());
        }

        /** @var callable $prompt */
        $prompt = $rc->newInstance();
        $messages = $prompt(...$args);

        $combined = implode("\n", array_map(
            static fn ($message): string => $message->content->text,
            $messages,
        ));

        $this->assertDoesNotMatchRegularExpression(self::ANY_PLACEHOLDER, $combined);
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
            $args[$param->getName()] = self::sampleValue($param->getName());
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

    public function test_task_type_outside_its_vocabulary_is_rejected_by_name(): void
    {
        // The prompt body gates destructive behaviour on this token ("for review, do not
        // rewrite the artefact"). An unrecognised value does not degrade: the branch never
        // fires and the model rewrites an artefact the user asked it only to review. The
        // four sibling prompts used to advertise three different vocabularies, so a client
        // could plausibly send AQL's `review-existing` to the template prompt.
        $prompt = new \Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewTemplate();

        $this->expectException(PromptGetException::class);
        $this->expectExceptionMessage('Prompt argument "task_type" must be one of: design | review — "review-existing" given.');
        $prompt('review-existing', 'concept', 'context', 'root');
    }

    public function test_task_type_is_canonicalised_so_capitalisation_cannot_miss_a_branch(): void
    {
        $prompt = new \Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewTemplate();
        $messages = $prompt('ReVieW', 'concept', 'context', 'root', '', 'the existing OET');

        $combined = implode("\n", array_map(static fn ($m): string => $m->content->text, $messages));
        $this->assertStringContainsString("Task type (design | review):\nreview", $combined);
        $this->assertStringNotContainsString('ReVieW', $combined);
    }

    /**
     * @return list<array{0: string, 1: list<string>, 2: string}>
     */
    public static function conditionalArtefactProvider(): array
    {
        return [
            'template review needs the template' => ['DesignOrReviewTemplate', ['review', 'c', 'ctx', 'root'], 'existing_template'],
            'aql review needs the query' => ['DesignOrReviewAql', ['review', 'intent'], 'existing_aql'],
            'archetype review needs the archetype' => ['DesignOrReviewArchetype', ['review', 'c', 'OBSERVATION', 'ctx'], 'existing_archetype'],
            'archetype specialise needs the parent' => ['DesignOrReviewArchetype', ['specialise', 'c', 'OBSERVATION', 'ctx'], 'parent_archetype'],
            'simplified review needs the payload' => ['DesignOrReviewSimplifiedFormat', ['review', 'tpl', 'flat'], 'existing_json'],
        ];
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('conditionalArtefactProvider')]
    public function test_review_without_its_artefact_is_rejected(string $class, array $args, string $expected): void
    {
        // Without this, `task_type: review` and no artefact renders "preserve the supplied
        // Existing X unchanged" directly above an empty slot, and the model confidently
        // reviews nothing.
        $rc = new ReflectionClass(self::PROMPTS_NAMESPACE . $class);
        /** @var callable $prompt */
        $prompt = $rc->newInstance();

        try {
            $prompt(...$args);
            $this->fail(sprintf('%s: expected %s to be required', $class, $expected));
        } catch (PromptGetException $e) {
            $this->assertStringContainsString($expected, $e->getMessage());
        }
    }

    public function test_shared_policy_carries_no_placeholders(): void
    {
        // shared/policy.md is prepended to *every* prompt and is substituted with whatever
        // arguments that prompt declares, so a `{{token}}` here would break all ten at once
        // — including the four parameterless explorers, whose value map is empty. The file
        // sits outside parameterizedPromptFiles()'s glob, so nothing else states this.
        $policy = file_get_contents(__DIR__ . '/../../resources/prompts/shared/policy.md');
        $this->assertIsString($policy);
        $this->assertDoesNotMatchRegularExpression(self::ANY_PLACEHOLDER, $policy);
    }

    public function test_every_markdown_placeholder_is_covered_by_invoke_parameters(): void
    {
        $promptsDir = __DIR__ . '/../../resources/prompts';

        foreach (self::parameterizedPromptFiles() as $file => $className) {
            $content = file_get_contents($promptsDir . '/' . $file);
            $this->assertIsString($content);

            // Scanned with the broad pattern so a malformed token is reported here rather
            // than silently skipped: the strict pattern would not see `{{adl-text}}` at all,
            // and production would then ship it verbatim.
            preg_match_all(self::ANY_PLACEHOLDER, $content, $matches);
            $tokens = array_unique($matches[0]);
            $this->assertNotEmpty($tokens, sprintf('%s expected to contain placeholders', $file));

            $rc = new ReflectionClass($className);
            $paramNames = array_map(
                static fn ($param): string => $param->getName(),
                $rc->getMethod('__invoke')->getParameters(),
            );

            foreach ($tokens as $token) {
                $this->assertMatchesRegularExpression(
                    '/^\{\{[a-z0-9_]+\}\}$/',
                    $token,
                    sprintf('%s: %s is not a substitutable placeholder (lower_snake_case, no spaces)', $file, $token),
                );

                $placeholder = trim($token, '{}');
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
