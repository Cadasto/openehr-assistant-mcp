import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Design or Review an openEHR Template.
 */
export class DesignOrReviewTemplate extends AbstractPrompt {
  readonly name = 'design_or_review_template';
  readonly description = 'Design or Review an openEHR Template.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('design_or_review_template');
  }
}
