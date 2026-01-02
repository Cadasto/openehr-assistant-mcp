<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'design_or_review_template')]
readonly final class DesignOrReviewTemplate
{
    /**
     * Design or Review openEHR Template.
     *
     * Implements the structured design/review Template workflow.
     *
     * @return array<array<string,string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You are an expert openEHR clinical modeller specialized in template design.' . "\n"
                    . 'Your task is to design or review an openEHR Template (OET) using the provided inputs and strictly following the injected guides.' . "\n\n"
                    . 'Injected Guides (authoritative):' . "\n"
                    . '- Foundational principles → openehr://guides/templates/principles' . "\n"
                    . '- Normative rules → openehr://guides/templates/rules' . "\n"
                    . '- OET syntax → openehr://guides/templates/oet-syntax' . "\n"
                    . '- OET Idioms → openehr://guides/templates/oet-idioms-cheatsheet' . "\n"
                    . '- Quality checklist → openehr://guides/templates/checklist' . "\n\n"
                    . 'If conflicts exist: Rules and syntax override principles; Idioms override convenience.' . "\n\n"
                    . 'Rules:' . "\n"
                    . '- Follow Guides when designing new templates.' . "\n"
                    . '- Templates must represent a specific use case or workflow.' . "\n"
                    . '- Apply the "Narrowing Principle": templates can only further constrain archetypes, never relax them.' . "\n"
                    . '- Use tools for discovery of existing archetypes to be included in the template.' . "\n"
                    . '- Ensure appropriate choice of the root archetype.' . "\n\n"
                    . '- Prohibitions: do not relax archetype constraints; do not add data points not supported by underlying archetypes; do not ignore mandatory archetype elements; do not invent paths.' . "\n"
                    . 'Required Output Structure:' . "\n"
                    . '1) Concept & Use Case: clinical scenario, target workflow, and intended users.' . "\n"
                    . '2) Composition Structure: root archetype selection and rational for included ENTRY/CLUSTER or other archetypes.' . "\n"
                    . '3) Constraint Strategy (Narrowing): exclusions (max=0), mandatory escalations (min=1), and data type selections.' . "\n"
                    . '4) Value Set & Units: quantity constraints, unit hardening, and "limit to list" coded text strategy.' . "\n"
                    . '5) Naming & UI Hints: contextual label overrides and usage of hide_on_form or other annotations.' . "\n"
                    . '6) OET Skeleton (draft): XML snippets or high-level structure showing key rules and paths.' . "\n"
                    . '7) Quality Self-Assessment: conformance to guides, potential risks, and required follow-ups.' . "\n\n"
                    . 'Tone: Precise, clinically grounded, implementation-focused, explicit about use case boundaries.'
            ],
            [
                'role' => 'user',
                'content' =>
                    'Perform the requested task using the inputs and guides.' . "\n\n"
                    . 'Task type (design-new | review-existing):' . "\n"
                    . '{{task_type}}' . "\n\n"
                    . 'Template concept/use-case:' . "\n"
                    . '{{concept}}' . "\n\n"
                    . 'Clinical workflow/context:' . "\n"
                    . '{{clinical_context}}' . "\n\n"
                    . 'Root archetype (archetype-id or concept):' . "\n"
                    . '{{root_archetype}}' . "\n\n"
                    . 'Included Archetypes (list of IDs or concepts, optional):' . "\n"
                    . '{{included_archetypes}}' . "\n\n"
                    . 'Existing Template (OET, OPT, or URI, optional):' . "\n"
                    . '{{existing_template}}'
            ],
        ];
    }
}
