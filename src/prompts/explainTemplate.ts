import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Explain openEHR Template semantics.
 */
export class ExplainTemplate extends AbstractPrompt {
  readonly name = 'explain_template';
  readonly description = 'Explain openEHR Template semantics.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('explain_template');
  }
}
