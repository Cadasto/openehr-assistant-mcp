import { readdirSync, readFileSync, statSync } from 'fs';
import { join, basename, relative } from 'path';
import type { Logger } from 'winston';
import { z } from 'zod';
import { APP_RESOURCES_DIR } from '../constants.js';

export const GuideSearchSchema = {
  query: z
    .string()
    .optional()
    .default('')
    .describe(
      'The query string describing what guidance you need (e.g. "cardinality vs occurrences", "slot constraints"). Leave empty to search all guides.',
    ),
  category: z
    .string()
    .optional()
    .default('')
    .describe(
      'Optional guide category filter (e.g. "archetypes", "templates"). Leave empty to search all.',
    ),
  taskType: z
    .string()
    .optional()
    .default('')
    .describe(
      'Optional task hint (e.g. "lint", "review", "refactor", "author"). If supplied, matches guides containing it.',
    ),
};

export const GuideGetSchema = {
  uri: z
    .string()
    .optional()
    .default('')
    .describe(
      'Canonical guide URI (openehr://guides/{category}/{name}). Optional when category and name are provided.',
    ),
  category: z
    .string()
    .optional()
    .default('')
    .describe('Guide category (e.g. "archetypes" or "templates"). Optional when URI is provided.'),
  name: z
    .string()
    .optional()
    .default('')
    .describe('Guide filename without extension. Optional when URI is provided.'),
};

export const GuideAdlIdiomLookupSchema = {
  pattern: z
    .string()
    .describe(
      'Symptom or pattern string to search within the ADL idioms cheatsheet (e.g. "occurrences vs cardinality", "coded text", "slots").',
    ),
};

interface GuideSearchItem {
  title: string;
  category: string;
  name: string;
  resourceUri: string;
  snippet: string;
  score: number;
}

interface GuideGetResult {
  uri: string;
  mimeType: string;
  text: string;
}

interface AdlIdiomItem {
  title: string;
  snippet: string;
  resourceUri: string;
  section: string;
}

interface GuideFile {
  path: string;
  category: string;
  name: string;
}

const DEFAULT_MAX_RESULTS = 15;
const DEFAULT_SECTION_LIMIT = 5;
const SNIPPET_CHARS = 350;

export class GuideService {
  static readonly GUIDE_DIR = `${APP_RESOURCES_DIR}/guides`;

  constructor(private readonly logger: Logger) {}

  /**
   * Search openEHR guides metadata and content to retrieve small, model-ready snippets.
   */
  search(query = '', category = '', taskType = ''): { items: GuideSearchItem[] } {
    this.logger.debug('called guideService.search', { query, category, taskType });
    query = query.trim();
    category = category.trim();
    taskType = taskType.trim();

    const results: GuideSearchItem[] = [];
    for (const fileInfo of this.getGuideFiles()) {
      const guide = this.extractGuide(fileInfo);
      if (category && guide.category !== category) continue;
      if (taskType && !guide.content.toLowerCase().includes(taskType.toLowerCase())) continue;

      results.push({
        title: guide.title,
        category: guide.category,
        name: guide.name,
        resourceUri: guide.resourceUri,
        snippet: this.buildSnippet(guide.content, query),
        score: this.scoreGuide(query, guide.title, guide.content, guide.category),
      });
    }

    results.sort((a, b) => b.score - a.score);
    return { items: results.slice(0, DEFAULT_MAX_RESULTS) };
  }

  /**
   * Fetch the full content of an openEHR guide by its canonical URI or by specifying category and name.
   */
  get(uri = '', category = '', name = ''): GuideGetResult {
    this.logger.debug('called guideService.get', { uri, category, name });
    uri = uri.trim();
    category = category.trim();
    name = name.trim();

    if (uri) {
      [category, name] = this.parseGuideUri(uri);
    }

    if (!category || !name) {
      throw new Error('Guide category and name are required when URI is not provided.');
    }

    const path = this.guidePath(category, name);
    let content: string;
    try {
      content = readFileSync(path, 'utf-8');
    } catch {
      throw new Error(`Guide not found: ${category}/${name}`);
    }

    if (!content) {
      throw new Error(`Guide content is empty: ${category}/${name}`);
    }

    return {
      uri: this.buildGuideUri(category, name),
      mimeType: 'text/markdown',
      text: content,
    };
  }

  /**
   * Lookup ADL idiom snippets for a symptom or pattern.
   */
  adlIdiomLookup(pattern: string): { items: AdlIdiomItem[] } {
    this.logger.debug('called guideService.adlIdiomLookup', { pattern });
    pattern = pattern.trim();
    if (!pattern) {
      return { items: [] };
    }

    const category = 'archetypes';
    const name = 'adl-idioms-cheatsheet';
    const path = this.guidePath(category, name);

    let content: string;
    try {
      content = readFileSync(path, 'utf-8');
    } catch {
      throw new Error('ADL idioms cheatsheet not found.');
    }

    const title = this.extractTitle(content, name);
    const sections = this.parseSections(content);

    const matches: (AdlIdiomItem & { score: number })[] = [];
    for (const section of sections) {
      const score = this.scoreGuide(pattern, section.title, section.content);
      if (score === 0) continue;
      matches.push({
        title,
        snippet: this.buildSnippet(section.content, pattern),
        resourceUri: this.buildGuideUri(category, name),
        section: section.title,
        score,
      });
    }

    matches.sort((a, b) => b.score - a.score);
    const limited = matches.slice(0, DEFAULT_SECTION_LIMIT + 2);
    return {
      items: limited.map(({ score: _score, ...rest }) => rest),
    };
  }

  private getGuideFiles(): GuideFile[] {
    const dir = GuideService.GUIDE_DIR;
    const files: GuideFile[] = [];
    try {
      this.walkDir(dir, files, dir);
    } catch {
      return [];
    }
    return files;
  }

  private walkDir(current: string, acc: GuideFile[], base: string): void {
    let entries: string[];
    try {
      entries = readdirSync(current);
    } catch {
      return;
    }
    for (const entry of entries) {
      const full = join(current, entry);
      try {
        const st = statSync(full);
        if (st.isDirectory()) {
          this.walkDir(full, acc, base);
        } else if (st.isFile() && entry.toLowerCase().endsWith('.md')) {
          const rel = relative(base, full);
          const parts = rel.split('/');
          const category = parts[0] ?? 'unknown';
          const name = basename(entry, '.md');
          acc.push({ path: full, category, name });
        }
      } catch {
        continue;
      }
    }
  }

  private extractGuide(fileInfo: GuideFile): {
    title: string;
    category: string;
    name: string;
    resourceUri: string;
    content: string;
  } {
    let content = '';
    try {
      content = readFileSync(fileInfo.path, 'utf-8');
    } catch {
      /* ignore */
    }
    return {
      title: this.extractTitle(content, fileInfo.name),
      category: fileInfo.category,
      name: fileInfo.name,
      resourceUri: this.buildGuideUri(fileInfo.category, fileInfo.name),
      content,
    };
  }

  private extractTitle(content: string, fallback: string): string {
    for (const line of content.split(/\r?\n/)) {
      const trimmed = line.trim();
      if (trimmed.startsWith('# ')) {
        return trimmed.slice(2).trim();
      }
    }
    return fallback;
  }

  private parseSections(content: string): { title: string; level: number; content: string }[] {
    const lines = content.split(/\r?\n/);
    const sections: { title: string; level: number; content: string }[] = [];
    let current = { title: 'Introduction', level: 2, content: '' };

    for (const line of lines) {
      const m = /^(#{2,3})\s+(.*)$/.exec(line.trim());
      if (m) {
        if (current.content.trim()) {
          sections.push(current);
        }
        current = { title: m[2].trim(), level: m[1].length, content: '' };
      } else {
        current.content += line + '\n';
      }
    }
    if (current.content.trim()) {
      sections.push(current);
    }
    return sections;
  }

  private scoreGuide(query: string, title: string, content: string, category = ''): number {
    const lowerContent = content.toLowerCase();
    const lowerTitle = title.toLowerCase();
    const keywords = query.trim().split(/\s+/).filter(Boolean);

    let score = 0;
    for (const kw of keywords) {
      const k = kw.toLowerCase();
      if (lowerTitle.includes(k)) score += 4;
      if (category && category.toLowerCase().includes(k)) score += 3;
      score += Math.min(
        (lowerContent.match(new RegExp(k.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g')) ?? [])
          .length,
        6,
      );
    }
    return score;
  }

  private buildSnippet(content: string, query: string): string {
    const lower = content.toLowerCase();
    const needle = query.toLowerCase();
    const pos = needle ? lower.indexOf(needle) : -1;
    if (pos === -1) {
      return this.limitText(content, SNIPPET_CHARS);
    }
    const start = Math.max(0, pos - Math.floor(SNIPPET_CHARS / 2));
    return content.slice(start, start + SNIPPET_CHARS).trim();
  }

  private limitText(text: string, maxChars: number): string {
    text = text.trim();
    if (text.length <= maxChars) return text;
    return text.slice(0, maxChars - 1).trimEnd() + '…';
  }

  private parseGuideUri(uri: string): [string, string] {
    const m = /^openehr:\/\/guides\/([\w-]+)\/([\w-]+)$/.exec(uri);
    if (!m) {
      throw new Error(`Invalid guide URI: ${uri}`);
    }
    return [m[1], m[2]];
  }

  guidePath(category: string, name: string): string {
    category = category.trim();
    name = name.trim();
    if (!category || !name) return '';
    return join(GuideService.GUIDE_DIR, category, `${name}.md`);
  }

  buildGuideUri(category: string, name: string): string {
    return `openehr://guides/${category}/${name}`;
  }
}
