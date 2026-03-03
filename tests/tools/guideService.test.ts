import { describe, it, expect, beforeEach } from 'vitest';
import winston from 'winston';
import { GuideService } from '../../src/tools/guideService.js';

function createNullLogger(): winston.Logger {
  return winston.createLogger({ silent: true });
}

describe('GuideService', () => {
  let service: GuideService;

  beforeEach(() => {
    service = new GuideService(createNullLogger());
  });

  describe('search', () => {
    it('should return matches for a keyword', () => {
      const results = service.search('cardinality');
      expect(results).toHaveProperty('items');
      expect(results.items.length).toBeGreaterThan(0);
      expect(results.items[0]).toHaveProperty('resourceUri');
      expect(results.items[0].resourceUri).toMatch(/^openehr:\/\/guides\//);
    });

    it('should filter by category', () => {
      const results = service.search('template', 'templates');
      expect(results.items.every((i) => i.category === 'templates')).toBe(true);
    });

    it('should return items with expected fields', () => {
      const results = service.search('archetype');
      if (results.items.length > 0) {
        const item = results.items[0];
        expect(item).toHaveProperty('title');
        expect(item).toHaveProperty('category');
        expect(item).toHaveProperty('name');
        expect(item).toHaveProperty('snippet');
        expect(item).toHaveProperty('score');
      }
    });

    it('should sort by score descending', () => {
      const results = service.search('archetype');
      for (let i = 1; i < results.items.length; i++) {
        expect(results.items[i - 1].score).toBeGreaterThanOrEqual(results.items[i].score);
      }
    });
  });

  describe('get', () => {
    it('should retrieve guide by URI', () => {
      const uri = 'openehr://guides/archetypes/adl-idioms-cheatsheet';
      const result = service.get(uri);
      expect(result.uri).toBe(uri);
      expect(result.mimeType).toBe('text/markdown');
      expect(result.text).toContain('idioms');
    });

    it('should retrieve guide by category and name', () => {
      const result = service.get('', 'archetypes', 'adl-idioms-cheatsheet');
      expect(result.uri).toBe('openehr://guides/archetypes/adl-idioms-cheatsheet');
      expect(result.text).toBeTruthy();
    });

    it('should throw when guide not found', () => {
      expect(() => service.get('openehr://guides/archetypes/non-existent')).toThrow();
    });

    it('should throw when URI is invalid', () => {
      expect(() => service.get('invalid://uri')).toThrow('Invalid guide URI');
    });

    it('should throw when category and name are missing', () => {
      expect(() => service.get('', '', '')).toThrow('Guide category and name are required');
    });
  });

  describe('adlIdiomLookup', () => {
    it('should return matching idiom snippets', () => {
      const results = service.adlIdiomLookup('cardinality');
      expect(results).toHaveProperty('items');
      expect(results.items.length).toBeGreaterThan(0);
      expect(results.items[0]).toHaveProperty('resourceUri');
      expect(results.items[0]).toHaveProperty('section');
    });

    it('should return empty items for empty pattern', () => {
      const results = service.adlIdiomLookup('');
      expect(results.items).toHaveLength(0);
    });

    it('should not include score in result items', () => {
      const results = service.adlIdiomLookup('cardinality');
      if (results.items.length > 0) {
        expect(results.items[0]).not.toHaveProperty('score');
      }
    });
  });
});
