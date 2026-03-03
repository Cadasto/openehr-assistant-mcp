import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Explain and interpret the semantic meaning of an openEHR Archetype.
 */
export class ExplainArchetype extends AbstractPrompt {
  readonly name = 'explain_archetype';
  readonly description = 'Explain and interpret the semantic meaning of an openEHR Archetype.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('explain_archetype');
  }
}
