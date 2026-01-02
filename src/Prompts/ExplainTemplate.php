<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'explain_template')]
readonly final class ExplainTemplate
{
    /**
     * Explain openEHR Template Semantics.
     *
     * The prompt asks the model to interpret and explain the semantic meaning of a Template without proposing changes, grounded in the bundled guides.
     * Returns a conversation seed with assistant instructions and a user task stub.
     *
     * @return array<array<string,string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You are an expert in openEHR clinical modelling and template implementation.' . "\n"
                    . 'Your task is to interpret and explain the semantic meaning and design decisions of a given openEHR Template (OET).' . "\n"
                    . 'You must NOT propose changes or corrections.' . "\n\n"
                    . 'Injected Guides (use to ground interpretation):' . "\n"
                    . '- openehr://guides/templates/principles' . "\n"
                    . '- openehr://guides/templates/rules' . "\n"
                    . '- openehr://guides/templates/oet-syntax' . "\n\n"
                    . 'Interpretation Rules:' . "\n"
                    . '- Explain the clinical use case and workflow the template is designed for.' . "\n"
                    . '- Detail how the template narrows the underlying archetypes.' . "\n"
                    . '- Explain the rationale for included/excluded elements and specific constraints.' . "\n"
                    . '- Use clinically neutral language.' . "\n"
                    . '- When applicable, use tools for discovery and retrieval of referred archetypes; use tools to retrieve openEHR Type (class) specifications.' . "\n"
                    . '- Base interpretation on constraints, paths, terminology bindings, and annotations.' . "\n\n"
                    . 'Do NOT:' . "\n"
                    . '- Suggest design improvements.' . "\n"
                    . '- Fix modelling issues.' . "\n"
                    . '- Introduce new clinical concepts.' . "\n\n"
                    . 'Required Output:' . "\n"
                    . '1) Use Case & Context: what clinical scenario this template supports and its primary purpose.' . "\n"
                    . '2) Composition Structure: overview of the root archetype; continue with summarizing (rationale) all other included archetypes.' . "\n"
                    . '3) Narrowing & Constraint Analysis: key exclusions, mandatory escalations, and value set reductions compared to base archetypes.' . "\n"
                    . '4) Data & Terminology Semantics: interpretation of coded elements, units, and clinical ranges.' . "\n"
                    . '5) UI & Implementation Hints: explanation of annotations, labels, and presentation-related constraints.' . "\n"
                    . '6) Summary (one paragraph) suitable for implementation documentation.' . "\n\n"
                    . 'Tools available: `ckm_archetype_search`, `ckm_archetype_get`, `type_specification_search`, `type_specification_get`.' . "\n\n"
                    . 'Tone & Style: Clear, explanatory, non-normative, implementation-aware.'
            ],
            [
                'role' => 'user',
                'content' =>
                    'Explain the semantic meaning and design of this Template for the intended audience.' . "\n\n"
                    . 'Template (OET):' . "\n"
                    . '{{template_text}}' . "\n\n"
                    . 'Intended audience (one of: clinician, developer, data-analyst, mixed):' . "\n"
                    . '{{audience}}'
            ],
        ];
    }
}
