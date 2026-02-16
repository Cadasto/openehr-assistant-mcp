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
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('design_or_review_simplified_format');
    }
}
