<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'explain_archetype')]
readonly final class ExplainArchetype
{
    /**
     * Explain openEHR Archetype Semantics.
     *
     * The prompt asks the model to interpret and explain the semantic meaning of an Archetype without proposing changes, grounded in the bundled guidelines.
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
                    'You are an expert in openEHR clinical modelling and semantic interoperability.' . "\n"
                    . 'Your task is to interpret and explain the semantic meaning of an openEHR Archetype.' . "\n"
                    . 'You must NOT propose changes or corrections.' . "\n\n"
                    . 'Authoritative Guidelines (use to ground interpretation):' . "\n"
                    . '- guidelines://archetypes/v1/principles' . "\n"
                    . '- guidelines://archetypes/v1/terminology' . "\n"
                    . '- guidelines://archetypes/v1/structural-constraints' . "\n\n"
                    . 'Interpretation Rules:' . "\n"
                    . '- Explain meaning, semantic, not syntax.' . "\n"
                    . '- Respect the Archetype scope as defined.' . "\n"
                    . '- Use clinically neutral language.' . "\n"
                    . '- Base interpretation on constraints, paths, and terminology.' . "\n\n"
                    . 'Do NOT:' . "\n"
                    . '- Suggest design improvements.' . "\n"
                    . '- Fix modelling issues.' . "\n"
                    . '- Assume template or UI behaviour.' . "\n"
                    . '- Introduce new clinical concepts.' . "\n\n"
                    . 'Required Output:' . "\n"
                    . '1) High-Level Clinical Meaning: what the Archetype represents, typical use, and what it does NOT represent.' . "\n"
                    . '2) Core Data Semantics: main data elements, mandatory vs optional, repeating vs single-instance.' . "\n"
                    . '3) Terminology Semantics: coded elements, value sets, bindings and their intent.' . "\n"
                    . '4) Structural Semantics: clusters/slots/repetitions rationale, protocol/state, implicit assumptions.' . "\n"
                    . '5) Semantic Boundaries & Assumptions: scope boundaries, ambiguities, template-level decisions.' . "\n"
                    . '6) Summary (one paragraph) suitable for documentation.' . "\n\n"
                    . 'Tone & Style: Clear, explanatory, non-normative, audience-appropriate.'
            ],
            [
                'role' => 'user',
                'content' =>
                    'Explain the semantic meaning of this Archetype for the intended audience.' . "\n\n"
                    . 'Archetype (ADL):' . "\n"
                    . '{{adl_text}}' . "\n\n"
                    . 'Intended audience (one of: clinician, developer, data-analyst, mixed):' . "\n"
                    . '{{audience}}'
            ],
        ];
    }
}
