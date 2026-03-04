<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use PHPUnit\Framework\TestCase;

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
            'ckm_archetype_explorer.md',
            'ckm_template_explorer.md',
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
        $this->assertStringContainsString('**Deployed OPT/templates:**', $aqlPrompt);
        $this->assertStringContainsString('Parameterize all variable inputs', $aqlPrompt);

        $translatePrompt = file_get_contents(__DIR__ . '/../../resources/prompts/translate_archetype_language.md');
        $this->assertIsString($translatePrompt);
        $this->assertStringContainsString('Keep all at-codes and ac-codes unchanged', $translatePrompt);
        $this->assertStringContainsString('Do NOT translate archetype class names', $translatePrompt);

        $simplifiedPrompt = file_get_contents(__DIR__ . '/../../resources/prompts/design_or_review_simplified_format.md');
        $this->assertIsString($simplifiedPrompt);
        $this->assertStringContainsString('Simplified Formats are **template-specific**', $simplifiedPrompt);
        $this->assertStringContainsString('Use **pipe suffixes**', $simplifiedPrompt);
    }
}
