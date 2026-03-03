import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Discover and retrieve openEHR terminology definitions.
 */
export class TerminologyExplorer extends AbstractPrompt {
  readonly name = 'terminology_explorer';
  readonly description = 'Discover and retrieve openEHR terminology definitions.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('terminology_explorer');
  }
}
