import type { PromptMessage } from './abstractPrompt.js';
import { AbstractPrompt } from './abstractPrompt.js';

/**
 * Discover and fetch openEHR Type specifications as BMM JSON.
 */
export class TypeSpecificationExplorer extends AbstractPrompt {
  readonly name = 'type_specification_explorer';
  readonly description = 'Discover and fetch openEHR Type specifications as BMM JSON.';

  invoke(): PromptMessage[] {
    return this.loadPromptMessages('type_specification_explorer');
  }
}
