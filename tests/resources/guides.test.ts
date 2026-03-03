import { describe, it, expect } from 'vitest';
import { readGuide, listGuideResources } from '../../src/resources/guides.js';

describe('Guides resource', () => {
  describe('readGuide', () => {
    it('should read a valid guide', () => {
      const content = readGuide('archetypes', 'adl-idioms-cheatsheet');
      expect(content).toBeTruthy();
      expect(content.length).toBeGreaterThan(0);
    });

    it('should throw for non-existent guide', () => {
      expect(() => readGuide('archetypes', 'non-existent')).toThrow();
    });

    it('should throw for invalid category', () => {
      expect(() => readGuide('', 'some-guide')).toThrow('Invalid guide resource identifier');
    });

    it('should throw for invalid name with special chars', () => {
      expect(() => readGuide('archetypes', '../etc/passwd')).toThrow('Invalid guide resource identifier');
    });
  });

  describe('listGuideResources', () => {
    it('should return an array of guide resources', () => {
      const resources = listGuideResources();
      expect(Array.isArray(resources)).toBe(true);
      expect(resources.length).toBeGreaterThan(0);
    });

    it('should return resources with required fields', () => {
      const resources = listGuideResources();
      for (const r of resources) {
        expect(r).toHaveProperty('uri');
        expect(r).toHaveProperty('name');
        expect(r).toHaveProperty('description');
        expect(r).toHaveProperty('mimeType', 'text/markdown');
        expect(r).toHaveProperty('handler');
        expect(r.uri).toMatch(/^openehr:\/\/guides\//);
        expect(typeof r.handler).toBe('function');
      }
    });

    it('should include archetypes category guides', () => {
      const resources = listGuideResources();
      const archetypeGuides = resources.filter((r) => r.uri.includes('/archetypes/'));
      expect(archetypeGuides.length).toBeGreaterThan(0);
    });
  });
});
