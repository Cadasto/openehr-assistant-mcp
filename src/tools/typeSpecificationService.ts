import { readdirSync, readFileSync, statSync } from 'fs';
import { join, basename } from 'path';
import type { Logger } from 'winston';
import { z } from 'zod';
import { APP_RESOURCES_DIR } from '../constants.js';

export const TypeSpecificationSearchSchema = {
  namePattern: z
    .string()
    .min(3)
    .describe(
      'Type-name pattern (min 3 chars); supports `*` wildcard. Examples: `ARCHETYPE_SLOT`, `ARCHETYPE_SL*`, `DV_*`.',
    ),
  keyword: z
    .string()
    .optional()
    .default('')
    .describe(
      'Optional raw substring filter applied to JSON content (case-insensitive); use to narrow results to types containing a concept or attribute name.',
    ),
};

export const TypeSpecificationGetSchema = {
  name: z
    .string()
    .describe('The openEHR Type name (e.g. `DV_QUANTITY`, `COMPOSITION`, etc.)'),
  component: z
    .string()
    .optional()
    .default('')
    .describe(
      'Optional component name (e.g. `RM`, `AM`, `BASE`); if omitted, first matching type is returned.',
    ),
};

interface BmmMetadata {
  name?: string;
  documentation?: string;
  package?: string;
  specUrl?: string;
  [key: string]: unknown;
}

interface TypeSpecificationItem {
  name: string;
  documentation?: string;
  resourceUri: string;
  component: string;
  package?: string;
  specUrl?: string;
}

export class TypeSpecificationService {
  static readonly BMM_DIR = `${APP_RESOURCES_DIR}/bmm`;

  constructor(private readonly logger: Logger) {}

  /**
   * Search for openEHR Type specifications by name pattern.
   */
  search(namePattern: string, keyword = ''): { items: TypeSpecificationItem[] } {
    this.logger.debug('called typeSpecificationService.search', { namePattern, keyword });
    namePattern = namePattern.trim();
    keyword = keyword.trim();

    if (!namePattern || namePattern.length < 3) {
      return { items: [] };
    }

    const regex = this.buildRegex(namePattern);
    const results: TypeSpecificationItem[] = [];

    for (const fileInfo of this.walkBmmDir()) {
      if (!regex.test(fileInfo.filename)) continue;
      try {
        const json = readFileSync(fileInfo.path, 'utf-8');
        if (!json) continue;
        if (keyword && !json.toLowerCase().includes(keyword.toLowerCase())) continue;

        const data = JSON.parse(json) as BmmMetadata;
        const name = data['name'] ?? fileInfo.filename.replace(/\.bmm\.json$/i, '');
        results.push({
          name: String(name),
          documentation: data['documentation'] !== undefined ? String(data['documentation']) : undefined,
          resourceUri: `openehr://spec/type/${fileInfo.component}/${name}`,
          component: fileInfo.component,
          package: data['package'] !== undefined ? String(data['package']) : undefined,
          specUrl: data['specUrl'] !== undefined ? String(data['specUrl']) : undefined,
        });
      } catch (e) {
        this.logger.error('Failed to read/parse BMM JSON', {
          file: fileInfo.path,
          error: String(e),
        });
      }
    }

    this.logger.info('BMM list results', {
      count: results.length,
      namePattern,
      keyword,
    });
    return { items: results };
  }

  /**
   * Retrieve the full specification of a specific openEHR Type as BMM JSON.
   */
  get(name: string, component = ''): BmmMetadata {
    this.logger.debug('called typeSpecificationService.get', { name, component });
    name = name.replace(/[.*\\/]/g, '').trim();
    if (!name) {
      throw new Error('Name cannot be empty');
    }

    const regex = this.buildRegex(name);
    for (const fileInfo of this.walkBmmDir()) {
      if (!regex.test(fileInfo.filename)) continue;
      this.logger.info('Found BMM', { pattern: fileInfo.filename });
      if (component && component !== fileInfo.component) {
        this.logger.info('Component not matching', { pattern: fileInfo.filename });
        continue;
      }
      try {
        const json = readFileSync(fileInfo.path, 'utf-8');
        return JSON.parse(json) as BmmMetadata;
      } catch (e) {
        this.logger.error('Failed to decode BMM JSON', {
          file: fileInfo.path,
          error: String(e),
        });
        throw new Error(`Failed to decode BMM JSON for type: ${name}`);
      }
    }

    this.logger.info('BMM not found', { name, component });
    throw new Error(`Type '${name}' not found (in '${component}' component).`);
  }

  private buildRegex(pattern: string): RegExp {
    const upper = pattern.toUpperCase().trim();
    const escaped = upper
      .replace(/[.+^${}()|[\]\\]/g, '\\$&')
      .replace(/\*/g, '[\\w-]*')
      .replace(/\?/g, '[\\w-]');
    return new RegExp(`^${escaped}\\.bmm\\.json$`, 'i');
  }

  private *walkBmmDir(): Generator<{ path: string; filename: string; component: string }> {
    const bmmDir = TypeSpecificationService.BMM_DIR;
    let componentDirs: string[];
    try {
      componentDirs = readdirSync(bmmDir).filter((d) => {
        try {
          return statSync(join(bmmDir, d)).isDirectory();
        } catch {
          return false;
        }
      });
    } catch {
      return;
    }

    for (const comp of componentDirs) {
      const compDir = join(bmmDir, comp);
      let files: string[];
      try {
        files = readdirSync(compDir);
      } catch {
        continue;
      }
      for (const file of files) {
        if (!file.toLowerCase().endsWith('.json')) continue;
        const filePath = join(compDir, file);
        try {
          const st = statSync(filePath);
          if (!st.isFile() || st.size === 0) continue;
        } catch {
          continue;
        }
        yield { path: filePath, filename: basename(filePath), component: comp };
      }
    }
  }
}
