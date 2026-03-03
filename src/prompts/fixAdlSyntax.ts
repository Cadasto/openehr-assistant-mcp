import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Correct or improve Archetype syntax without changing semantics.
 */
export class FixAdlSyntax extends AbstractPrompt {
  readonly name = 'fix_adl_syntax';
  readonly description = 'Correct or improve Archetype syntax without changing semantics.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('fix_adl_syntax');
  }
}
