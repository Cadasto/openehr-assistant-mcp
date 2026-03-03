import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Translate an archetype's terminology section between languages with safety checks.
 */
export class TranslateArchetypeLanguage extends AbstractPrompt {
  readonly name = 'translate_archetype_language';
  readonly description = 'Translate an archetype's terminology section between languages with safety checks.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('translate_archetype_language');
  }
}
