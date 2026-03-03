import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Guided workflow to discover and retrieve Archetypes from CKM.
 */
export class CkmArchetypeExplorer extends AbstractPrompt {
  readonly name = 'ckm_archetype_explorer';
  readonly description = 'Guided workflow to discover and retrieve Archetypes from CKM.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('ckm_archetype_explorer');
  }
}
