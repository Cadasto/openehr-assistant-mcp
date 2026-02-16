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
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('design_or_review_aql');
    }
}
