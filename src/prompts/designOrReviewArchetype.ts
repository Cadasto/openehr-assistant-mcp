import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Design or Review an openEHR Archetype based on provided inputs and guides.
 */
export class DesignOrReviewArchetype extends AbstractPrompt {
  readonly name = 'design_or_review_archetype';
  readonly description = 'Design or Review an openEHR Archetype based on provided inputs and guides.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('design_or_review_archetype');
  }
}
