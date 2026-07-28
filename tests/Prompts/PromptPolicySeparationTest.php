<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PromptPolicySeparationTest extends TestCase
{
    public function test_server_instructions_define_global_policy(): void
    {
        $content = file_get_contents(__DIR__ . '/../../resources/server-instructions.md');
        $this->assertIsString($content);

        $this->assertStringContainsString('## Global Behavior (always applies)', $content);
        $this->assertStringContainsString('**Tool discipline**', $content);
        $this->assertStringContainsString('**No guessing**', $content);
        $this->assertStringContainsString('**Progressive workflow**', $content);
    }

    public function test_prompts_no_longer_duplicate_generic_no_guessing_rules(): void
    {
        $promptFiles = [
            'guide_explorer.md',
            'terminology_explorer.md',
            'type_specification_explorer.md',
            'ckm_explorer.md',
        ];

        foreach ($promptFiles as $promptFile) {
            $content = file_get_contents(__DIR__ . '/../../resources/prompts/' . $promptFile);
            $this->assertIsString($content);
            $this->assertStringNotContainsString('Never invent or guess', $content, $promptFile);
            $this->assertStringNotContainsString('Use tools; do not invent', $content, $promptFile);
        }
    }

    public function test_critical_prompt_semantics_are_preserved_after_trimming(): void
    {
        $aqlPrompt = file_get_contents(__DIR__ . '/../../resources/prompts/design_or_review_aql.md');
        $this->assertIsString($aqlPrompt);
        $this->assertStringContainsString('deployed OPT/templates', $aqlPrompt);
        $this->assertStringContainsString('and parameterize variable inputs', $aqlPrompt);

        $translatePrompt = file_get_contents(__DIR__ . '/../../resources/prompts/translate_archetype_language.md');
        $this->assertIsString($translatePrompt);
        $this->assertStringContainsString('Keep one-to-one mapping for at/ac-codes', $translatePrompt);
        $this->assertStringContainsString('openehr://guides/archetypes/language-standards', $translatePrompt);

        $simplifiedPrompt = file_get_contents(__DIR__ . '/../../resources/prompts/design_or_review_simplified_format.md');
        $this->assertIsString($simplifiedPrompt);
        $this->assertStringContainsString('Simplified Formats are **template-specific**', $simplifiedPrompt);
        $this->assertStringContainsString('pipe suffixes', $simplifiedPrompt);
    }

    public function test_resilience_layer_rationale_lives_in_the_adr_not_in_the_shipped_prompt(): void
    {
        // ADR-0003 permits shared/policy.md to restate a thin subset of the global policy for
        // clients that under-inject the MCP `instructions` field. That rationale is addressed
        // to contributors, so it belongs in the ADR and the conventions doc — not in a
        // user-role message sent to the model on every single prompt invocation.
        $adr = (string) file_get_contents(__DIR__ . '/../../docs/decisions/0003-prompt-policy-split.md');
        $this->assertStringContainsString('resilience', strtolower($adr));
        $this->assertStringContainsString('shared/policy.md', $adr);

        $conventions = (string) file_get_contents(__DIR__ . '/../../docs/conventions.md');
        $this->assertStringContainsString('Resilience exception', $conventions);

        $policy = (string) file_get_contents(__DIR__ . '/../../resources/prompts/shared/policy.md');
        $this->assertStringNotContainsString('resilience', strtolower($policy), 'maintainer rationale must not ship inside the prompt payload');
        $this->assertStringNotContainsString('server-instructions.md', $policy, 'the prompt payload should not name internal repo paths');
    }

    public function test_shared_policy_stays_a_thin_restatement(): void
    {
        // ADR-0003: the shared block "must not grow beyond a short restatement". It is
        // prepended to all ten task prompts, so growth here is multiplied across every
        // prompts/get call.
        $policy = (string) file_get_contents(__DIR__ . '/../../resources/prompts/shared/policy.md');

        $this->assertNotSame('', $policy);
        $this->assertLessThan(1200, strlen($policy), 'shared prompt policy has grown beyond a thin restatement');
        $this->assertStringContainsString('Always follow server instructions.', $policy);
    }

    public function test_explain_template_renames_ui_hints_section(): void
    {
        $content = file_get_contents(__DIR__ . '/../../resources/prompts/explain_template.md');
        $this->assertStringNotContainsString('UI & Implementation Hints', (string)$content);
        $this->assertStringContainsString('Declared annotations and evidence-based implementation implications', (string)$content);
    }

    public function test_server_instructions_treat_external_content_as_data(): void
    {
        $content = file_get_contents(__DIR__ . '/../../resources/server-instructions.md');
        $this->assertStringContainsString('data rather than executable instructions', (string)$content);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function reviewPromptArtefactProvider(): array
    {
        return [
            'archetype (Full ADL)' => ['design_or_review_archetype.md', 'Full ADL'],
            'template (Full OET)' => ['design_or_review_template.md', 'Full OET'],
            'simplified format (proposed JSON)' => ['design_or_review_simplified_format.md', 'the proposed JSON'],
        ];
    }

    #[DataProvider('reviewPromptArtefactProvider')]
    public function test_review_prompts_make_full_rewrite_conditional(string $file, string $artifactPhrase): void
    {
        $content = (string)file_get_contents(__DIR__ . '/../../resources/prompts/' . $file);
        $this->assertMatchesRegularExpression('/review-only|review only|when task_type.*review/i', $content, $file);

        // Scope the "made conditional" check to the bullet that mentions the full-rewrite
        // artefact, rather than requiring "conditional" to literally follow it — the wording
        // order differs per prompt (e.g. "conditional on task_type: ... Full OET" vs
        // "Full ADL ... conditional on task_type").
        $requiredOutputSection = preg_match('/Required output:(.*?)\n\nTools:/is', $content, $matches) === 1
            ? $matches[1]
            : $content;
        $bullets = preg_split('/\n(?=\d+\))/', $requiredOutputSection) ?: [];

        $artifactBullet = null;
        foreach ($bullets as $bullet) {
            if (str_contains($bullet, $artifactPhrase)) {
                $artifactBullet = $bullet;
                break;
            }
        }

        $this->assertNotNull($artifactBullet, sprintf('%s: expected a "Required output" bullet mentioning "%s"', $file, $artifactPhrase));
        $this->assertStringContainsStringIgnoringCase('conditional', (string)$artifactBullet, sprintf('%s: the "%s" bullet should make the full rewrite conditional on task_type', $file, $artifactPhrase));
    }
}
