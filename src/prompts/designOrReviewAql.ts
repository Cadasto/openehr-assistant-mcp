import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Design or Review an AQL query using AQL guides.
 */
export class DesignOrReviewAql extends AbstractPrompt {
  readonly name = 'design_or_review_aql';
  readonly description = 'Design or Review an AQL query using AQL guides.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('design_or_review_aql');
  }
}
