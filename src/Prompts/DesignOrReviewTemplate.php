<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'design_or_review_template')]
readonly final class DesignOrReviewTemplate extends AbstractPrompt
{
    /**
     * Design or Review an openEHR Template, based on the provided inputs and guides.
     *
     * @param mixed $task_type design | review
     * @param mixed $concept Template concept/use-case
     * @param mixed $clinical_context Clinical workflow/context
     * @param mixed $root_archetype Root archetype (archetype-id or concept)
     * @param mixed $included_archetypes Included Archetypes (list of IDs or concepts, optional)
     * @param mixed $existing_template Existing Template (OET, OPT, or URI) — optional for design, expected when task_type is review
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $task_type,
        mixed $concept,
        mixed $clinical_context,
        mixed $root_archetype,
        mixed $included_archetypes = '',
        mixed $existing_template = '',
    ): array {
        return $this->loadPromptMessages('design_or_review_template', [
            'task_type' => $task_type,
            'concept' => $concept,
            'clinical_context' => $clinical_context,
            'root_archetype' => $root_archetype,
            'included_archetypes' => $included_archetypes,
            'existing_template' => $existing_template,
        ], ['task_type', 'concept', 'clinical_context', 'root_archetype']);
    }
}
