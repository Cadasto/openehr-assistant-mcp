import { readdirSync, existsSync } from 'fs';
import { join } from 'path';
import { APP_RESOURCES_DIR } from '../constants.js';

export class GuidesCompletionProvider {
  private readonly directories: string[];

  constructor() {
    const base = join(APP_RESOURCES_DIR, 'guides');
    this.directories = [
      join(base, 'archetypes'),
      join(base, 'templates'),
      join(base, 'aql'),
      join(base, 'simplified_formats'),
    ];
  }

  getCompletions(currentValue: string): string[] {
    const files: string[] = [];
    for (const dir of this.directories) {
      if (!existsSync(dir)) continue;
      try {
        for (const file of readdirSync(dir)) {
          if (file.endsWith('.md')) {
            files.push(file.slice(0, -3));
          }
        }
      } catch {
        continue;
      }
    }

    const unique = [...new Set(files)];
    if (!currentValue) return unique;
    return unique.filter((f) => f.startsWith(currentValue));
  }
}
