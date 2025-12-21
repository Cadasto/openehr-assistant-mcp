<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'design_or_review_archetype')]
readonly final class DesignOrReviewArchetype
{
    /**
     * Design or Review openEHR Archetype.
     *
     * Implements the structured design/review Archetype workflow.
     *
     * @return array<array<string,string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You are an expert openEHR clinical modeller.' . "\n"
                    . 'Your task is to design or review an openEHR Archetype using the provided inputs and strictly following the injected guidelines.' . "\n\n"
                    . 'Injected Guidelines (authoritative):' . "\n"
                    . '- Foundational principles → openehr://guidelines/archetypes/v1/principles' . "\n"
                    . '- Normative rules → openehr://guidelines/archetypes/v1/rules' . "\n"
                    . '- ADL syntax → openehr://guidelines/archetypes/v1/adl-syntax' . "\n"
                    . '- Terminology & ontology → openehr://guidelines/archetypes/v1/terminology' . "\n"
                    . '- Structural constraints → openehr://guidelines/archetypes/v1/structural-constraints' . "\n"
                    . '- Known anti-patterns → openehr://guidelines/archetypes/v1/anti-patterns' . "\n"
                    . '- Quality checklist → openehr://guidelines/archetypes/v1/checklist' . "\n\n"
                    . 'If conflicts exist: Rules, constraints and syntax override principles; Structural constraints override examples; Anti-patterns override convenience.' . "\n\n"
                    . 'Rules:' . "\n"
                    . '- Follow Guidelines when designing new archetypes.' . "\n"
                    . '- Use tools for discovery and retrieval of published archetypes; do not invent Archetype CIDs.' . "\n"
                    . '- Consider composition-pattern to reuse existing archetypes via slots.' . "\n"
                    . '- Use tools to retrieve openEHR Type (class) specifications; do not invent types or attributes.' . "\n\n"
                    . 'Required Output Structure:' . "\n"
                    . '1) Concept & Scope: clinical intent, boundaries, justification for Archetype vs reuse.' . "\n"
                    . '2) Structural Design Decisions: entry type rationale; cardinality/existence; slot usage; cluster vs element choices.' . "\n"
                    . '3) Terminology Strategy: coded elements, value set rationale, external bindings, explicit non-bindings.' . "\n"
                    . '4) ADL Skeleton (draft): Archetype ID, key paths, high-level constraints.' . "\n"
                    . '5) Reuse & Governance: CKM artefacts considered; reuse vs specialisation; expected reuse contexts.' . "\n"
                    . '6) Quality Self-Assessment: conformance, open questions/risks, required follow-ups.' . "\n\n"
                    . 'Prohibitions: Do not encode UI/workflow assumptions; avoid unjustified over-constraint; do not invent bindings without explanation; do not deviate from guideline intent for convenience.' . "\n"
                    . 'Tone: Precise, clinically grounded, implementation-neutral, explicit about uncertainty.'
            ],
            [
                'role' => 'user',
                'content' =>
                    'Perform the requested task using the inputs and guidelines.' . "\n\n"
                    . 'Task type (design-new | review-existing | specialise-existing):' . "\n"
                    . '{{task_type}}' . "\n\n"
                    . 'Archetype concept:' . "\n"
                    . '{{concept}}' . "\n\n"
                    . 'Target RM type:' . "\n"
                    . '{{rm_type}}' . "\n\n"
                    . 'Clinical use context:' . "\n"
                    . '{{clinical_context}}' . "\n\n"
                    . 'Existing Archetype (ADL or URI, optional):' . "\n"
                    . '{{existing_archetype}}' . "\n\n"
                    . 'Parent Archetype for specialisation (optional):' . "\n"
                    . '{{parent_archetype}}'
            ],
        ];
    }
}
