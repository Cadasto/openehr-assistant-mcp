import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Explain the intent, structure, and semantics of an AQL query using AQL guides.
 */
export class ExplainAql extends AbstractPrompt {
  readonly name = 'explain_aql';
  readonly description = 'Explain the intent, structure, and semantics of an AQL query using AQL guides.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('explain_aql');
  }
}
