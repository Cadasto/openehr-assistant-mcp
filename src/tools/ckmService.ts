import type { Logger } from 'winston';
import { z } from 'zod';
import type { CkmClient } from '../apis/ckmClient.js';
import { Map as FormatMap } from '../helpers/map.js';

// ──────────────────────────────────────────────────────────────────────────────
// Input schemas
// ──────────────────────────────────────────────────────────────────────────────

export const ArchetypeSearchSchema = {
  keyword: z
    .string()
    .describe(
      'Query search string (one or multiple words); wildcards `*` supported; prefer meaningful clinical terms, e.g. "blood pressure", "medication".',
    ),
  limit: z.number().int().optional().default(20).describe('Maximum number of results; default 20.'),
  offset: z.number().int().optional().default(0).describe('Offset for paging; default 0.'),
  requireAllSearchWords: z
    .boolean()
    .optional()
    .default(true)
    .describe('Match all search words (true) or any of them (false); default true.'),
};

export const ArchetypeGetSchema = {
  identifier: z
    .string()
    .describe(
      'Archetype CID (e.g. "1013.1.7850") or archetype-id (e.g. "openEHR-EHR-OBSERVATION.blood_pressure.v1").',
    ),
  format: z
    .enum(['adl', 'xml', 'mindmap'])
    .optional()
    .default('adl')
    .describe('Desired format: "adl", "xml", or "mindmap"; default "adl".'),
};

export const TemplateSearchSchema = {
  keyword: z
    .string()
    .describe('Query search string; one or multiple words; wildcards `*` supported.'),
  limit: z.number().int().optional().default(20).describe('Maximum number of results; default 20.'),
  offset: z.number().int().optional().default(0).describe('Offset for paging; default 0.'),
  requireAllSearchWords: z
    .boolean()
    .optional()
    .default(true)
    .describe('Match all search words (true) or any of them (false); default true.'),
};

export const TemplateGetSchema = {
  identifier: z.string().describe('Template CID identifier (e.g. "1013.26.244").'),
  format: z
    .enum(['opt', 'oet'])
    .optional()
    .default('opt')
    .describe(
      'Desired format: "oet" (design-time template source) or "opt" (flattened operational template); default "opt".',
    ),
};

// ──────────────────────────────────────────────────────────────────────────────
// Return types
// ──────────────────────────────────────────────────────────────────────────────

interface ArchetypeItem {
  cid?: string;
  archetypeId?: string;
  name?: string;
  projectName?: string;
  status?: string;
  revision?: string;
  creationTime?: string;
  modificationTime?: string;
  score: number;
}

interface SearchResult<T> {
  items: T[];
  total: number;
}

// ──────────────────────────────────────────────────────────────────────────────
// Service
// ──────────────────────────────────────────────────────────────────────────────

export class CkmService {
  constructor(
    private readonly apiClient: CkmClient,
    private readonly logger: Logger,
  ) {}

  /**
   * Search and discover candidate openEHR Archetypes in the Clinical Knowledge Manager (CKM).
   */
  async archetypeSearch(
    keyword: string,
    limit = 20,
    offset = 0,
    requireAllSearchWords = true,
  ): Promise<SearchResult<ArchetypeItem>> {
    this.logger.debug('called archetypeSearch', { keyword, limit, offset, requireAllSearchWords });
    try {
      const response = await this.apiClient.get('v1/archetypes', {
        params: {
          'search-text': keyword,
          size: limit,
          offset,
          'restrict-search-to-main-data': 'true',
          'require-all-search-words': requireAllSearchWords ? 'true' : 'false',
          'sort-key': 'RELEVANCE',
        },
        headers: { Accept: 'application/json' },
      });

      const rawData: unknown[] = Array.isArray(response.data) ? response.data : [];
      this.logger.info('Found CKM Archetypes', { keyword, count: rawData.length });

      const data: ArchetypeItem[] = rawData.map((item: unknown) => {
        const r = item as Record<string, unknown>;
        const entry: ArchetypeItem = {
          cid: r['cid'] as string | undefined,
          archetypeId: r['resourceMainId'] as string | undefined,
          name: r['resourceMainDisplayName'] as string | undefined,
          projectName: r['projectName'] as string | undefined,
          status: r['status'] as string | undefined,
          revision: r['revision'] as string | undefined,
          creationTime: r['creationTime'] as string | undefined,
          modificationTime:
            (r['modificationTime'] as string | undefined) ??
            (r['creationTime'] as string | undefined),
          score: 0,
        };

        for (const k of keyword.trim().split(/\s+/)) {
          if (entry.archetypeId && entry.archetypeId.toLowerCase().includes(k.toLowerCase())) {
            entry.score += 4;
          } else if (entry.name && entry.name.toLowerCase().includes(k.toLowerCase())) {
            entry.score += 3;
          }
          if (entry.projectName && entry.projectName.toLowerCase().includes(k.toLowerCase())) {
            entry.score += 2;
          }
        }

        if (
          entry.projectName &&
          ['common resources', 'structural archetypes'].includes(entry.projectName.toLowerCase())
        ) {
          entry.score += 1;
        }

        if (entry.status) {
          entry.score += this.statusScore(entry.status);
        }

        // Remove undefined fields
        return Object.fromEntries(
          Object.entries(entry).filter(([, v]) => v !== undefined),
        ) as ArchetypeItem;
      });

      data.sort((a, b) => b.score - a.score);

      const totalHeader = response.headers['x-total-count'];
      return {
        items: data,
        total: totalHeader !== undefined ? parseInt(String(totalHeader), 10) : data.length,
      };
    } catch (e) {
      this.logger.error('Failed to search for CKM Archetypes', { error: String(e) });
      throw new Error(`Failed to search for CKM Archetypes: ${String(e)}`);
    }
  }

  /**
   * Retrieve the full definition of an Archetype from CKM.
   */
  async archetypeGet(identifier: string, format = 'adl'): Promise<string> {
    this.logger.debug('called archetypeGet', { identifier, format });
    identifier = identifier.trim();
    let cid: string | null = null;

    try {
      const archetypeFormat = FormatMap.archetypeFormat(format);
      const contentType = FormatMap.contentType(archetypeFormat);

      // Resolve archetype-id to CID
      if (identifier.includes('openEHR-')) {
        try {
          const res = await this.apiClient.get(`v1/archetypes/citeable-identifier/${identifier}`);
          if (res.status === 200) {
            cid = String(res.data).trim();
          }
        } catch (e) {
          this.logger.error('Failed to resolve CID identifier', {
            error: String(e),
            identifier,
          });
        }
      }

      // Normalize identifier to CID if not yet resolved
      cid = cid ?? identifier.replace(/[^0-9.]/g, '-');

      const response = await this.apiClient.get(`v1/archetypes/${cid}/${archetypeFormat}`, {
        headers: { Accept: contentType },
        responseType: 'text',
      });

      const text = String(response.data).trim();
      this.logger.info('CKM Archetype retrieved successfully', {
        cid,
        format: archetypeFormat,
        status: response.status,
      });

      return '```\n' + text + '\n```';
    } catch (e) {
      this.logger.error('Failed to retrieve the CKM Archetype', {
        error: String(e),
        identifier,
        cid,
        format,
      });
      throw new Error(`Failed to retrieve the CKM Archetype: ${String(e)}`);
    }
  }

  /**
   * Search for candidate openEHR Templates in the CKM.
   */
  async templateSearch(
    keyword: string,
    limit = 20,
    offset = 0,
    requireAllSearchWords = true,
  ): Promise<SearchResult<Record<string, unknown>>> {
    this.logger.debug('called templateSearch', { keyword, limit, offset, requireAllSearchWords });
    try {
      const response = await this.apiClient.get('v1/templates', {
        params: {
          'search-text': keyword,
          size: limit,
          offset,
          'template-type': 'NORMAL',
          'restrict-search-to-main-data': 'true',
          'require-all-search-words': requireAllSearchWords ? 'true' : 'false',
          'sort-key': 'RELEVANCE',
        },
        headers: { Accept: 'application/json' },
      });

      const rawData: unknown[] = Array.isArray(response.data) ? response.data : [];
      this.logger.info('Found CKM Templates', { keyword, count: rawData.length });

      const data = rawData.map((item: unknown) => {
        const r = item as Record<string, unknown>;
        const entry: Record<string, unknown> = {
          cid: r['cid'],
          name: r['resourceMainDisplayName'],
          projectName: r['projectName'],
          status: r['status'],
          version: r['versionAsset'],
          creationTime: r['creationTime'],
          modificationTime: r['modificationTime'] ?? r['creationTime'],
          score: 0,
        };

        const score = entry['score'] as number;
        let s = score;
        for (const k of keyword.trim().split(/\s+/)) {
          if (entry['name'] && String(entry['name']).toLowerCase().includes(k.toLowerCase())) {
            s += 3;
          }
          if (
            entry['projectName'] &&
            String(entry['projectName']).toLowerCase().includes(k.toLowerCase())
          ) {
            s += 2;
          }
        }
        if (
          entry['projectName'] &&
          ['common resources', 'structural archetypes'].includes(
            String(entry['projectName']).toLowerCase(),
          )
        ) {
          s += 1;
        }
        if (entry['status']) {
          s += this.statusScore(String(entry['status']));
        }
        entry['score'] = s;

        return Object.fromEntries(Object.entries(entry).filter(([, v]) => v !== undefined));
      });

      data.sort((a, b) => (b['score'] as number) - (a['score'] as number));

      const totalHeader = response.headers['x-total-count'];
      return {
        items: data,
        total: totalHeader !== undefined ? parseInt(String(totalHeader), 10) : data.length,
      };
    } catch (e) {
      this.logger.error('Failed to search for CKM Templates', { error: String(e) });
      throw new Error(`Failed to search for CKM Templates: ${String(e)}`);
    }
  }

  /**
   * Retrieve the full definition of an openEHR Template from CKM.
   */
  async templateGet(identifier: string, format = 'opt'): Promise<string> {
    this.logger.debug('called templateGet', { identifier, format });
    identifier = identifier.trim();

    try {
      const templateFormat = FormatMap.templateFormat(format);
      const contentType = FormatMap.contentType(templateFormat);

      const response = await this.apiClient.get(`v1/templates/${identifier}/${templateFormat}`, {
        headers: { Accept: contentType },
        responseType: 'text',
      });

      const text = String(response.data).trim();
      this.logger.info('CKM Template retrieved successfully', {
        cid: identifier,
        format: templateFormat,
        status: response.status,
      });

      return '```\n' + text + '\n```';
    } catch (e) {
      this.logger.error('Failed to retrieve the CKM Template', {
        error: String(e),
        identifier,
        format,
      });
      throw new Error(`Failed to retrieve the CKM Template: ${String(e)}`);
    }
  }

  private statusScore(status: string): number {
    switch (status.toUpperCase()) {
      case 'PUBLISHED':
        return 4;
      case 'TEAMREVIEW':
        return 2;
      case 'DRAFT':
      case 'REVIEWSUSPENDED':
        return -1;
      case 'INITIAL':
        return -2;
      default:
        return 0;
    }
  }
}
