<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\AbstractPrompt;
use Cadasto\OpenEHR\MCP\Assistant\Prompts\CkmExplorer;
use Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewAql;
use Cadasto\OpenEHR\MCP\Assistant\Prompts\ExplainAql;
use Cadasto\OpenEHR\MCP\Assistant\Prompts\GuideExplorer;
use Cadasto\OpenEHR\MCP\Assistant\Prompts\TerminologyExplorer;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractPrompt::class)]
final class PromptCompositionTest extends TestCase
{
    #[DataProvider('promptProvider')]
    public function testPromptRoleSequenceAndLegacyConstraintsArePreserved(callable $prompt, array $mustContain): void
    {
        $messages = $prompt();

        $this->assertNotEmpty($messages);
        $this->assertSame(Role::User, $messages[0]->role);
        $this->assertSame(Role::User, $messages[array_key_last($messages)]->role);

        $combined = implode("\n", array_map(static fn($message): string => $message->content->text, $messages));
        foreach ($mustContain as $needle) {
            $this->assertStringContainsString($needle, $combined);
        }
    }

    public static function promptProvider(): array
    {
        return [
            'guide explorer' => [static fn (): array => (new GuideExplorer())(), ['guide_search', 'guide_get']],
            'terminology explorer' => [static fn (): array => (new TerminologyExplorer())(), ['openEHR Terminology definitions', 'openehr://terminology']],
            'explain aql' => [
                static fn (): array => (new ExplainAql())(aql_query: 'SELECT c FROM EHR e CONTAINS COMPOSITION c'),
                ['openehr://guides/aql/principles', 'archetype path'],
            ],
            'design/review aql' => [
                static fn (): array => (new DesignOrReviewAql())(task_type: 'design', query_intent: 'latest BP per EHR'),
                ['openehr://guides/aql/checklist', 'design'],
            ],
            'ckm explorer' => [static fn (): array => (new CkmExplorer())(), ['ckm_archetype_search', 'ckm_archetype_get', 'ckm_template_search', 'ckm_template_get']],
        ];
    }

    /**
     * Per-prompt size ceilings (REQ-N7). The fixture is a budget, not a record of a past
     * reduction: four budgets were raised when the review prompts gained `{{placeholder}}`
     * scaffolding and conditional-output wording. Raising one is a deliberate act — state why
     * in the commit, and keep the remaining slack small enough that the ceiling still bites.
     */
    public function testPromptMarkdownStaysWithinItsSizeBudget(): void
    {
        $fixturesPath = APP_DIR . '/tests/fixtures/prompt_lengths_before_shared.json';
        $budgets = json_decode((string)file_get_contents($fixturesPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($budgets);

        // Every template on disk, not every entry in the fixture: a new prompt with no budget
        // must fail here rather than go unmeasured. shared/policy.md is not a task template; its
        // own cap is in PromptPolicySeparationTest.
        $promptFiles = glob(APP_DIR . '/resources/prompts/*.md') ?: [];
        $this->assertNotEmpty($promptFiles, 'expected prompt templates under resources/prompts/');
        $names = array_map(basename(...), $promptFiles);

        $this->assertSame([], array_values(array_diff($names, array_keys($budgets))), 'These prompt templates have no budget in tests/fixtures/prompt_lengths_before_shared.json');
        $this->assertSame([], array_values(array_diff(array_keys($budgets), $names)), 'These budgets name no prompt template under resources/prompts/; rename or remove them');

        foreach ($promptFiles as $path) {
            $file = basename($path);
            $budget = $budgets[$file];
            $current = (string)file_get_contents($path);
            $this->assertLessThan($budget['chars'], strlen($current), sprintf('%s exceeds its char budget', $file));
            $this->assertLessThan($budget['words'], str_word_count($current), sprintf('%s exceeds its word budget', $file));
        }
    }
}
