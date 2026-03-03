import { readFileSync, existsSync } from 'fs';
import { XMLParser } from 'fast-xml-parser';
import { APP_RESOURCES_DIR } from '../constants.js';
import { join } from 'path';

export const TERMINOLOGY_FILE_PATH = join(
  APP_RESOURCES_DIR,
  'terminology/openehr_terminology.xml',
);

interface CodeNode {
  '@_value': string;
}

interface CodesetNode {
  '@_name': string;
  '@_issuer': string;
  '@_openehr_id': string;
  '@_external_id': string;
  code: CodeNode | CodeNode[];
}

interface ConceptNode {
  '@_id': string;
  '@_rubric': string;
}

interface GroupNode {
  '@_name': string;
  '@_openehr_id': string;
  concept: ConceptNode | ConceptNode[];
}

interface TerminologyXml {
  terminology: {
    codeset?: CodesetNode | CodesetNode[];
    group?: GroupNode | GroupNode[];
  };
}

interface CodesetResult {
  name: string;
  issuer: string;
  openehr_id: string;
  external_id: string;
  codeset: string[];
}

interface GroupResult {
  name: string;
  openehr_id: string;
  group: Record<string, string>;
}

export interface TerminologyData {
  codesets: CodesetResult[];
  groups: GroupResult[];
}

let cached: TerminologyData | null = null;

/**
 * Read the full openEHR Terminology dataset.
 */
export function readTerminologies(): TerminologyData {
  if (cached) return cached;

  if (!existsSync(TERMINOLOGY_FILE_PATH)) {
    throw new Error('Terminology file not found or not readable.');
  }

  let content: string;
  try {
    content = readFileSync(TERMINOLOGY_FILE_PATH, 'utf-8');
  } catch (e) {
    throw new Error(`Unable to read terminology file: ${String(e)}`);
  }

  const parser = new XMLParser({ ignoreAttributes: false, attributeNamePrefix: '@_' });
  let data: TerminologyXml;
  try {
    data = parser.parse(content) as TerminologyXml;
  } catch (e) {
    throw new Error(`Error parsing Terminology XML: ${String(e)}`);
  }

  const results: TerminologyData = { codesets: [], groups: [] };

  // Parse codesets
  const codesets = data?.terminology?.codeset;
  const codesetArr = codesets ? (Array.isArray(codesets) ? codesets : [codesets]) : [];
  for (const cs of codesetArr) {
    const codes = cs.code ? (Array.isArray(cs.code) ? cs.code : [cs.code]) : [];
    results.codesets.push({
      name: cs['@_name'] ?? '',
      issuer: cs['@_issuer'] ?? '',
      openehr_id: cs['@_openehr_id'] ?? '',
      external_id: cs['@_external_id'] ?? '',
      codeset: codes.map((c) => c['@_value'] ?? ''),
    });
  }

  // Parse groups
  const groups = data?.terminology?.group;
  const groupArr = groups ? (Array.isArray(groups) ? groups : [groups]) : [];
  for (const g of groupArr) {
    const concepts = g.concept ? (Array.isArray(g.concept) ? g.concept : [g.concept]) : [];
    const conceptMap: Record<string, string> = {};
    for (const c of concepts) {
      conceptMap[c['@_id'] ?? ''] = c['@_rubric'] ?? '';
    }
    results.groups.push({
      name: g['@_name'] ?? '',
      openehr_id: g['@_openehr_id'] ?? '',
      group: conceptMap,
    });
  }

  cached = results;
  return results;
}
