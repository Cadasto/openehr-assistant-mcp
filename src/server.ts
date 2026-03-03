import { McpServer, ResourceTemplate } from '@modelcontextprotocol/sdk/server/mcp.js';
import { readFileSync, existsSync } from 'fs';
import { join } from 'path';
import { z } from 'zod';
import type { Logger } from 'winston';

import {
  APP_TITLE,
  APP_VERSION,
  APP_DESCRIPTION,
  APP_RESOURCES_DIR,
} from './constants.js';

import { CkmClient } from './apis/ckmClient.js';
import { CkmService, ArchetypeSearchSchema, ArchetypeGetSchema, TemplateSearchSchema, TemplateGetSchema } from './tools/ckmService.js';
import { TerminologyService, TerminologyResolveSchema } from './tools/terminologyService.js';
import { TypeSpecificationService, TypeSpecificationSearchSchema, TypeSpecificationGetSchema } from './tools/typeSpecificationService.js';
import { GuideService, GuideSearchSchema, GuideGetSchema, GuideAdlIdiomLookupSchema } from './tools/guideService.js';

import { readGuide, listGuideResources, guidesCompletionProvider, GUIDES_DIR } from './resources/guides.js';
import { readTerminologies } from './resources/terminologies.js';
import { readTypeSpecification, specificationComponentsProvider } from './resources/typeSpecifications.js';

// Prompts
import { CkmArchetypeExplorer } from './prompts/ckmArchetypeExplorer.js';
import { CkmTemplateExplorer } from './prompts/ckmTemplateExplorer.js';
import { DesignOrReviewAql } from './prompts/designOrReviewAql.js';
import { DesignOrReviewArchetype } from './prompts/designOrReviewArchetype.js';
import { DesignOrReviewSimplifiedFormat } from './prompts/designOrReviewSimplifiedFormat.js';
import { DesignOrReviewTemplate } from './prompts/designOrReviewTemplate.js';
import { ExplainAql } from './prompts/explainAql.js';
import { ExplainArchetype } from './prompts/explainArchetype.js';
import { ExplainSimplifiedFormat } from './prompts/explainSimplifiedFormat.js';
import { ExplainTemplate } from './prompts/explainTemplate.js';
import { FixAdlSyntax } from './prompts/fixAdlSyntax.js';
import { GuideExplorer } from './prompts/guideExplorer.js';
import { TerminologyExplorer } from './prompts/terminologyExplorer.js';
import { TranslateArchetypeLanguage } from './prompts/translateArchetypeLanguage.js';
import { TypeSpecificationExplorer } from './prompts/typeSpecificationExplorer.js';

export function buildServer(logger: Logger): McpServer {
  // Load server instructions
  const instructionsPath = join(APP_RESOURCES_DIR, 'server-instructions.md');
  const instructions = existsSync(instructionsPath)
    ? readFileSync(instructionsPath, 'utf-8')
    : undefined;

  // Instantiate server
  const server = new McpServer(
    { name: APP_TITLE, version: APP_VERSION },
    {
      capabilities: {
        tools: {},
        prompts: {},
        resources: { listChanged: false },
        logging: {},
      },
      instructions,
    },
  );

  // ──────────────────────────────────────────────────────────────────────────
  // Services
  // ──────────────────────────────────────────────────────────────────────────
  const ckmClient = new CkmClient(logger);
  const ckmService = new CkmService(ckmClient, logger);
  const terminologyService = new TerminologyService(logger);
  const typeSpecService = new TypeSpecificationService(logger);
  const guideService = new GuideService(logger);

  // ──────────────────────────────────────────────────────────────────────────
  // Tools
  // ──────────────────────────────────────────────────────────────────────────

  server.tool(
    'ckm_archetype_search',
    `Search and discover candidate openEHR Archetypes in the Clinical Knowledge Manager (CKM).
Use when you need to discover candidate archetypes before fetching their full definitions.
Typical LLM workflow: 1) Search by domain keyword, 2) Inspect returned metadata, 3) Use ckm_archetype_get to retrieve full definition.`,
    ArchetypeSearchSchema,
    async ({ keyword, limit, offset, requireAllSearchWords }) => {
      const result = await ckmService.archetypeSearch(keyword, limit, offset, requireAllSearchWords);
      return { content: [{ type: 'text', text: JSON.stringify(result) }] };
    },
  );

  server.tool(
    'ckm_archetype_get',
    `Retrieve the full definition of an Archetype from CKM in a specified format.
Use after identifying a candidate archetype from ckm_archetype_search, or when you already know the CID or archetype-id.
Formats: "adl" (best for semantics/constraints), "xml" (XML tooling), "mindmap" (quick visual overview).`,
    ArchetypeGetSchema,
    async ({ identifier, format }) => {
      const text = await ckmService.archetypeGet(identifier, format);
      return { content: [{ type: 'text', text }] };
    },
  );

  server.tool(
    'ckm_template_search',
    `Search for candidate openEHR Templates in the Clinical Knowledge Manager (CKM).
Typical LLM workflow: 1) Search by domain keywords, 2) Inspect returned metadata, 3) Use ckm_template_get to retrieve full definition.`,
    TemplateSearchSchema,
    async ({ keyword, limit, offset, requireAllSearchWords }) => {
      const result = await ckmService.templateSearch(keyword, limit, offset, requireAllSearchWords);
      return { content: [{ type: 'text', text: JSON.stringify(result) }] };
    },
  );

  server.tool(
    'ckm_template_get',
    `Retrieve the full definition of an openEHR Template (OET or OPT) from CKM.
Use after identifying a candidate template from ckm_template_search, or when you already know the CID.
Formats: "oet" (design-time template source), "opt" (flattened operational template with all archetype constraints).`,
    TemplateGetSchema,
    async ({ identifier, format }) => {
      const text = await ckmService.templateGet(identifier, format);
      return { content: [{ type: 'text', text }] };
    },
  );

  server.tool(
    'terminology_resolve',
    `Resolve an openEHR Terminology concept ID to its rubric, or find the concept ID for a given rubric.
Matching: numeric input = concept ID lookup; non-numeric = rubric lookup (case-insensitive).
Optional groupId restricts search to a specific terminology group.`,
    TerminologyResolveSchema,
    ({ input, groupId }) => {
      const result = terminologyService.resolve(input, groupId);
      return { content: [{ type: 'text', text: JSON.stringify(result) }] };
    },
  );

  server.tool(
    'type_specification_search',
    `Search for openEHR Type specifications by name pattern with optional keyword filter.
Supports glob-like `*` wildcard in namePattern (min 3 chars).
Returns metadata with canonical resource URIs for use with type_specification_get.`,
    TypeSpecificationSearchSchema,
    ({ namePattern, keyword }) => {
      const result = typeSpecService.search(namePattern, keyword);
      return { content: [{ type: 'text', text: JSON.stringify(result) }] };
    },
  );

  server.tool(
    'type_specification_get',
    `Retrieve the full specification of a specific openEHR Type (class) as BMM JSON.
Use to inspect properties/attributes, inheritance, and semantic constraints for a given type.
The component parameter is optional but improves matching accuracy.`,
    TypeSpecificationGetSchema,
    ({ name, component }) => {
      const result = typeSpecService.get(name, component);
      return { content: [{ type: 'text', text: JSON.stringify(result) }] };
    },
  );

  server.tool(
    'guide_search',
    `Search openEHR guides metadata and content to retrieve small, model-ready snippets with canonical openehr://guides URIs.
Use to locate the right guidance on demand; returns short, task-relevant chunks.
Follow with guide_get to retrieve the full guide content.`,
    GuideSearchSchema,
    ({ query, category, taskType }) => {
      const result = guideService.search(query, category, taskType);
      return { content: [{ type: 'text', text: JSON.stringify(result) }] };
    },
  );

  server.tool(
    'guide_get',
    `Fetch the full content of an openEHR guide by its canonical URI or by specifying its category and name.
Guides describe modeling workflows, best practices, syntax checklists, and other guidance on demand.`,
    GuideGetSchema,
    ({ uri, category, name }) => {
      const result = guideService.get(uri, category, name);
      return {
        content: [
          {
            type: 'resource',
            resource: {
              uri: result.uri,
              mimeType: result.mimeType,
              text: result.text,
            },
          },
        ],
      };
    },
  );

  server.tool(
    'guide_adl_idiom_lookup',
    `Lookup targeted ADL idiom snippets from the cheatsheet for common modelling patterns.
Provide a symptom or pattern (e.g. "occurrences vs cardinality", "coded text", "slots") to receive matching examples.`,
    GuideAdlIdiomLookupSchema,
    ({ pattern }) => {
      const result = guideService.adlIdiomLookup(pattern);
      return { content: [{ type: 'text', text: JSON.stringify(result) }] };
    },
  );

  // ──────────────────────────────────────────────────────────────────────────
  // Prompts
  // ──────────────────────────────────────────────────────────────────────────

  const promptInstances = [
    new CkmArchetypeExplorer(),
    new CkmTemplateExplorer(),
    new DesignOrReviewAql(),
    new DesignOrReviewArchetype(),
    new DesignOrReviewSimplifiedFormat(),
    new DesignOrReviewTemplate(),
    new ExplainAql(),
    new ExplainArchetype(),
    new ExplainSimplifiedFormat(),
    new ExplainTemplate(),
    new FixAdlSyntax(),
    new GuideExplorer(),
    new TerminologyExplorer(),
    new TranslateArchetypeLanguage(),
    new TypeSpecificationExplorer(),
  ];

  for (const prompt of promptInstances) {
    server.prompt(prompt.name, prompt.description, () => {
      const messages = prompt.invoke();
      return {
        messages: messages.map((m) => ({
          role: m.role,
          content: m.content,
        })),
      };
    });
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Resources - Static guide files
  // ──────────────────────────────────────────────────────────────────────────

  // Register each guide as a discoverable resource
  for (const guide of listGuideResources()) {
    const { uri, name, description, mimeType, handler } = guide;
    server.resource(name, uri, { description, mimeType }, () => ({
      contents: [{ uri, mimeType, text: handler() }],
    }));
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Resource Templates
  // ──────────────────────────────────────────────────────────────────────────

  // Guide resource template with completion providers
  server.resource(
    'guides',
    new ResourceTemplate('openehr://guides/{category}/{name}', {
      list: undefined,
      complete: {
        category: (_value: string) => ({
          completion: { values: ['archetypes', 'templates', 'aql', 'simplified_formats'] },
        }),
        name: (value: string) => ({
          completion: { values: guidesCompletionProvider.getCompletions(value) },
        }),
      },
    }),
    {
      name: 'guides',
      description:
        'The openEHR Assistant guides document (markdown) identified by category and name',
      mimeType: 'text/markdown',
    },
    (uri, { category, name }) => {
      const catStr = String(category ?? '');
      const nameStr = String(name ?? '');
      const text = readGuide(catStr, nameStr);
      return { contents: [{ uri: uri.href, mimeType: 'text/markdown', text }] };
    },
  );

  // Type specification resource template
  server.resource(
    'type_specification',
    new ResourceTemplate('openehr://spec/type/{component}/{name}', {
      list: undefined,
      complete: {
        component: (value: string) => ({
          completion: { values: specificationComponentsProvider.getCompletions(value) },
        }),
      },
    }),
    {
      name: 'type_specification',
      description:
        'An openEHR Type specification identified by component and type name, expressed in BMM JSON format',
      mimeType: 'application/json',
    },
    (uri, { component, name }) => {
      const data = readTypeSpecification(String(component ?? ''), String(name ?? ''));
      return {
        contents: [
          {
            uri: uri.href,
            mimeType: 'application/json',
            text: JSON.stringify(data, null, 2),
          },
        ],
      };
    },
  );

  // Terminology resource (static URI)
  server.resource(
    'terminology',
    'openehr://terminology',
    {
      name: 'terminology',
      description:
        'Full openEHR Terminology dataset including all groups (concept-rubric pairs) and codesets.',
      mimeType: 'application/json',
    },
    (uri) => {
      const data = readTerminologies();
      return {
        contents: [
          {
            uri: uri.href,
            mimeType: 'application/json',
            text: JSON.stringify(data, null, 2),
          },
        ],
      };
    },
  );

  return server;
}
