import { readdirSync, statSync, existsSync } from 'fs';
import { join } from 'path';
import { APP_RESOURCES_DIR } from '../constants.js';

export class SpecificationComponentsCompletionProvider {
  private readonly directory: string;

  constructor() {
    this.directory = join(APP_RESOURCES_DIR, 'bmm');
  }

  getCompletions(currentValue: string): string[] {
    if (!existsSync(this.directory)) return [];

    const completions: string[] = [];
    try {
      for (const entry of readdirSync(this.directory)) {
        const full = join(this.directory, entry);
        try {
          if (statSync(full).isDirectory()) {
            if (!currentValue || entry.startsWith(currentValue)) {
              completions.push(entry);
            }
          }
        } catch {
          continue;
        }
      }
    } catch {
      return [];
    }

    return completions;
  }
}
