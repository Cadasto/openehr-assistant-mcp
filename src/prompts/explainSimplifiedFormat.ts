import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Explain context, paths, and data elements of a Flat or Structured JSON payload.
 */
export class ExplainSimplifiedFormat extends AbstractPrompt {
  readonly name = 'explain_simplified_format';
  readonly description = 'Explain context, paths, and data elements of a Flat or Structured JSON payload.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('explain_simplified_format');
  }
}
