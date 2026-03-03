import { readdirSync, readFileSync, statSync, existsSync } from 'fs';
import { join, basename, relative } from 'path';
import { APP_DIR } from '../constants.js';
import { GuidesCompletionProvider } from '../completionProviders/guides.js';

export const GUIDES_DIR = join(APP_DIR, 'resources/guides');

export interface GuideResource {
  uri: string;
  name: string;
  description: string;
  mimeType: string;
  size: number;
  handler: () => string;
}

/**
 * Read a guide markdown file from the resources/guides tree.
 *
 * URI template: openehr://guides/{category}/{name}
 */
export function readGuide(category: string, name: string): string {
  for (const segment of [category, name]) {
    if (!segment || !/^[\w-]+$/.test(segment)) {
      throw new Error(`Invalid guide resource identifier: ${segment}`);
    }
  }

  const path = join(GUIDES_DIR, category, `${name}.md`);
  if (!existsSync(path)) {
    throw new Error(`Guide not found: ${category}/${name}`);
  }

  try {
    return readFileSync(path, 'utf-8');
  } catch (e) {
    throw new Error(`Unable to read guide ${category}/${name} content: ${String(e)}`);
  }
}

/**
 * List all registered guide resources.
 * Scans the guides directory and returns metadata for each markdown file.
 */
export function listGuideResources(): GuideResource[] {
  if (!existsSync(GUIDES_DIR)) return [];

  const resources: GuideResource[] = [];
  walkDir(GUIDES_DIR, GUIDES_DIR, resources);
  return resources;
}

function walkDir(current: string, base: string, acc: GuideResource[]): void {
  let entries: string[];
  try {
    entries = readdirSync(current);
  } catch {
    return;
  }

  for (const entry of entries) {
    const full = join(current, entry);
    let st;
    try {
      st = statSync(full);
    } catch {
      continue;
    }

    if (st.isDirectory()) {
      walkDir(full, base, acc);
    } else if (st.isFile() && entry.toLowerCase().endsWith('.md')) {
      const rel = relative(base, full);
      const parts = rel.split('/');
      if (parts.length < 2) continue;

      let content: string;
      try {
        content = readFileSync(full, 'utf-8');
      } catch {
        continue;
      }
      if (!content) continue;

      const category = parts[0];
      const name = basename(entry, '.md');
      const firstLine = content.split('\n')[0] ?? '';
      const description =
        firstLine.replace(/^#+\s*/, '').trim() || `Guide ${name} for ${category}`;

      acc.push({
        uri: `openehr://guides/${category}/${name}`,
        name: `guide_${category}_${name}`,
        description,
        mimeType: 'text/markdown',
        size: Buffer.byteLength(content, 'utf-8'),
        handler: () => content,
      });
    }
  }
}

export const guidesCompletionProvider = new GuidesCompletionProvider();
