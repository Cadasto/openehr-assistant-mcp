import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Discover and retrieve openEHR implementation guides.
 */
export class GuideExplorer extends AbstractPrompt {
  readonly name = 'guide_explorer';
  readonly description = 'Discover and retrieve openEHR implementation guides.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('guide_explorer');
  }
}
