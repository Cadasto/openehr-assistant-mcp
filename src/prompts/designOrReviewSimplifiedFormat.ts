import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Design or review a Flat or Structured (simplified) format instance using Simplified Formats guides.
 */
export class DesignOrReviewSimplifiedFormat extends AbstractPrompt {
  readonly name = 'design_or_review_simplified_format';
  readonly description = 'Design or review a Flat or Structured (simplified) format instance using Simplified Formats guides.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('design_or_review_simplified_format');
  }
}
