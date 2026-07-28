<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'explain_archetype')]
readonly final class ExplainArchetype extends AbstractPrompt
{
    /**
     * Explain and interpret the semantic meaning of an openEHR Archetype, grounded in the bundled guides.
     *
     * @param mixed $adl_text Archetype (ADL) to explain
     * @param mixed $audience Intended audience: clinician | implementer | modeller (optional)
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $adl_text,
        mixed $audience = '',
    ): array {
        return $this->loadPromptMessages('explain_archetype', [
            'adl_text' => $adl_text,
            'audience' => $audience,
        ], ['adl_text']);
    }
}
