<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'explain_simplified_format')]
readonly final class ExplainSimplifiedFormat extends AbstractPrompt
{
    /**
     * Explain a Flat or Structured (simplified) format instance using the Simplified Formats guides.
     *
     * @param mixed $json_payload Flat or Structured JSON to explain
     * @param mixed $template_id Target template, if known (optional)
     * @param mixed $context Use case, audience (optional)
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $json_payload,
        mixed $template_id = '',
        mixed $context = '',
    ): array {
        return $this->loadPromptMessages('explain_simplified_format', [
            'json_payload' => $json_payload,
            'template_id' => $template_id,
            'context' => $context,
        ], ['json_payload']);
    }
}
