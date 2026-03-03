import { readFileSync, existsSync } from 'fs';
import { XMLParser } from 'fast-xml-parser';
import type { Logger } from 'winston';
import { z } from 'zod';
import { APP_RESOURCES_DIR } from '../constants.js';

export const TerminologyResolveSchema = {
  input: z
    .string()
    .describe(
      'The concept ID (e.g. "433") or concept rubric (e.g. "event") to resolve.',
    ),
  groupId: z
    .string()
    .optional()
    .default('')
    .describe(
      'Optional openEHR terminology group ID (e.g. "composition_category") to restrict the search.',
    ),
};

export interface TerminologyResult {
  id: string;
  rubric: string;
  groupId: string;
  groupName: string;
}

interface ConceptNode {
  '@_id': string;
  '@_rubric': string;
}

interface GroupNode {
  '@_openehr_id': string;
  '@_name': string;
  concept: ConceptNode | ConceptNode[];
}

interface TerminologyXml {
  terminology: {
    group: GroupNode | GroupNode[];
  };
}

export class TerminologyService {
  static readonly FILE_PATH = `${APP_RESOURCES_DIR}/terminology/openehr_terminology.xml`;

  constructor(private readonly logger: Logger) {}

  /**
   * Resolve an openEHR Terminology concept ID to its rubric, or find the concept ID for a given rubric.
   */
  resolve(input: string, groupId = ''): TerminologyResult {
    this.logger.debug('called terminologyService.resolve', { input, groupId });

    input = input.trim();
    groupId = groupId.trim();

    if (!input) {
      throw new Error('Input cannot be empty.');
    }

    if (groupId && /\W/.test(groupId)) {
      throw new Error(`Invalid terminology group ID: ${groupId}`);
    }

    const data = this.loadXml();
    const groups = this.getGroups(data);
    const isId = /^\d+$/.test(input);

    const filteredGroups = groupId
      ? groups.filter((g) => g['@_openehr_id'] === groupId.toLowerCase())
      : groups;

    if (groupId && filteredGroups.length === 0) {
      throw new Error(`Terminology group "${groupId}" not found.`);
    }

    for (const group of filteredGroups) {
      const concepts = Array.isArray(group.concept) ? group.concept : [group.concept];
      for (const concept of concepts) {
        if (!concept) continue;
        const id = String(concept['@_id'] ?? '');
        const rubric = String(concept['@_rubric'] ?? '');
        const matches = isId
          ? id === input
          : rubric.toLowerCase() === input.toLowerCase();

        if (matches) {
          return {
            id,
            rubric,
            groupId: group['@_openehr_id'],
            groupName: group['@_name'],
          };
        }
      }
    }

    const suffix = groupId ? ` within group "${groupId}"` : '';
    throw new Error(`Could not resolve "${input}"${suffix} in openEHR terminology.`);
  }

  private loadXml(): TerminologyXml {
    const path = TerminologyService.FILE_PATH;
    if (!existsSync(path)) {
      this.logger.error('Terminology file not found or not readable.', { path });
      throw new Error('Terminology file not found or not readable.');
    }

    let content: string;
    try {
      content = readFileSync(path, 'utf-8');
    } catch (e) {
      throw new Error(`Unable to read terminology file: ${String(e)}`);
    }

    const parser = new XMLParser({ ignoreAttributes: false, attributeNamePrefix: '@_' });
    let data: TerminologyXml;
    try {
      data = parser.parse(content) as TerminologyXml;
    } catch (e) {
      this.logger.error('Error parsing terminology XML', { error: String(e) });
      throw new Error(`Error parsing terminology XML: ${String(e)}`);
    }

    const groups = this.getGroups(data);
    if (!groups.length) {
      this.logger.error('Terminology does not contain groups.');
      throw new Error('No terminology groups found.');
    }

    return data;
  }

  private getGroups(data: TerminologyXml): GroupNode[] {
    const raw = data?.terminology?.group;
    if (!raw) return [];
    return Array.isArray(raw) ? raw : [raw];
  }
}
