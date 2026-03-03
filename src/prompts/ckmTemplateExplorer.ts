import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Guided workflow to discover and retrieve openEHR Templates (OET or OPT) from CKM.
 */
export class CkmTemplateExplorer extends AbstractPrompt {
  readonly name = 'ckm_template_explorer';
  readonly description = 'Guided workflow to discover and retrieve openEHR Templates (OET or OPT) from CKM.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('ckm_template_explorer');
  }
}
