<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\CkmArchetypeExplorer;
use Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewAql;
use Cadasto\OpenEHR\MCP\Assistant\Prompts\GuideExplorer;
use Cadasto\OpenEHR\MCP\Assistant\Prompts\TerminologyExplorer;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PromptCompositionTest extends TestCase
{
    #[DataProvider('promptProvider')]
    public function testPromptRoleSequenceAndLegacyConstraintsArePreserved(object $prompt, array $mustContain): void
    {
        $messages = $prompt();

        $this->assertNotEmpty($messages);
        $this->assertSame(Role::Assistant, $messages[0]->role);
        $this->assertSame(Role::User, $messages[array_key_last($messages)]->role);

        $combined = implode("\n", array_map(static fn ($message): string => $message->content->text, $messages));
        foreach ($mustContain as $needle) {
            $this->assertStringContainsString($needle, $combined);
        }
    }

    public static function promptProvider(): array
    {
        return [
            'guide explorer' => [new GuideExplorer(), ['guide_search', 'guide_get']],
            'terminology explorer' => [new TerminologyExplorer(), ['openEHR Terminology definitions', 'openehr://terminology']],
            'explain aql' => [new \Cadasto\OpenEHR\MCP\Assistant\Prompts\ExplainAql(), ['openehr://guides/aql/principles', 'archetype path']],
            'design/review aql' => [new DesignOrReviewAql(), ['openehr://guides/aql/checklist', '{{task_type}}']],
            'ckm archetype' => [new CkmArchetypeExplorer(), ['ckm_archetype_search', 'ckm_archetype_get']],
        ];
    }

    public function testPromptMarkdownIsReducedVersusPriorFixtures(): void
    {
        $fixturesPath = APP_BASE_DIR . '/tests/Prompts/Fixtures/prompt_lengths_before_shared.json';
        $fixtures = json_decode((string) file_get_contents($fixturesPath), true, 512, JSON_THROW_ON_ERROR);

        foreach ($fixtures as $file => $baseline) {
            $currentPath = APP_BASE_DIR . '/resources/prompts/' . $file;
            $current = (string) file_get_contents($currentPath);
            $this->assertLessThan($baseline['chars'], strlen($current), sprintf('%s chars not reduced', $file));
            $this->assertLessThan($baseline['words'], str_word_count($current), sprintf('%s words not reduced', $file));
        }
    }
}
