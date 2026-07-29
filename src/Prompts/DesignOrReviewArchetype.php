<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'design_or_review_archetype')]
readonly final class DesignOrReviewArchetype extends AbstractPrompt
{
    /**
     * Design or Review an openEHR Archetype, based on the provided inputs and guides.
     *
     * @param mixed $task_type design | review | specialise
     * @param mixed $concept Archetype concept
     * @param mixed $rm_type Target RM type
     * @param mixed $clinical_context Clinical use context
     * @param mixed $existing_archetype Existing Archetype (ADL or URI) — optional for design, required when task_type is review
     * @param mixed $parent_archetype Parent Archetype — optional for design, required when task_type is specialise
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $task_type,
        mixed $concept,
        mixed $rm_type,
        mixed $clinical_context,
        mixed $existing_archetype = '',
        mixed $parent_archetype = '',
    ): array {
        return $this->loadPromptMessages(
            'design_or_review_archetype',
            [
                'task_type' => $task_type,
                'concept' => $concept,
                'rm_type' => $rm_type,
                'clinical_context' => $clinical_context,
                'existing_archetype' => $existing_archetype,
                'parent_archetype' => $parent_archetype,
            ],
            ['task_type', 'concept', 'rm_type', 'clinical_context'],
            ['task_type' => ['design', 'review', 'specialise']],
            [
                'existing_archetype' => ['task_type' => ['review']],
                'parent_archetype' => ['task_type' => ['specialise']],
            ],
        );
    }
}
