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
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('design_or_review_template');
    }
}
