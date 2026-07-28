<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'design_or_review_aql')]
readonly final class DesignOrReviewAql extends AbstractPrompt
{
    /**
     * Design or review an AQL query, based on the provided inputs and AQL guides.
     *
     * @param mixed $task_type design-new | review-existing
     * @param mixed $query_intent Clinical question or query intent
     * @param mixed $template_or_archetypes Target template or archetypes (optional)
     * @param mixed $existing_aql Existing AQL — optional for design-new, expected when task_type is review-existing
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $task_type,
        mixed $query_intent,
        mixed $template_or_archetypes = '',
        mixed $existing_aql = '',
    ): array {
        return $this->loadPromptMessages('design_or_review_aql', [
            'task_type' => $task_type,
            'query_intent' => $query_intent,
            'template_or_archetypes' => $template_or_archetypes,
            'existing_aql' => $existing_aql,
        ], ['task_type', 'query_intent']);
    }
}
