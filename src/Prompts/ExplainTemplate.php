<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'explain_template')]
readonly final class ExplainTemplate extends AbstractPrompt
{
    /**
     * Explain and interpret the semantic meaning of an openEHR Template, grounded in the bundled guides.
     *
     * @param mixed $template_text Template (OET) to explain
     * @param mixed $audience Intended audience: clinician, developer, data-analyst, mixed (optional)
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $template_text,
        mixed $audience = '',
    ): array {
        return $this->loadPromptMessages('explain_template', [
            'template_text' => $template_text,
            'audience' => $audience,
        ], ['template_text']);
    }
}
