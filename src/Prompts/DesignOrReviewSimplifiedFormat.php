<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'design_or_review_simplified_format')]
readonly final class DesignOrReviewSimplifiedFormat extends AbstractPrompt
{
    /**
     * Design or review a Flat/Structured (simplified) format instance using the Simplified Formats guides.
     *
     * @param mixed $task_type design | review
     * @param mixed $template_id Target template (OPT id or name)
     * @param mixed $format_variant flat | structured
     * @param mixed $existing_json Existing Flat/Structured JSON — optional for design, expected when task_type is review
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $task_type,
        mixed $template_id,
        mixed $format_variant,
        mixed $existing_json = '',
    ): array {
        return $this->loadPromptMessages('design_or_review_simplified_format', [
            'task_type' => $task_type,
            'template_id' => $template_id,
            'format_variant' => $format_variant,
            'existing_json' => $existing_json,
        ], ['task_type', 'template_id', 'format_variant']);
    }
}
