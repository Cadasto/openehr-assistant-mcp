import { describe, it, expect, beforeEach } from 'vitest';
import winston from 'winston';
import { TypeSpecificationService } from '../../src/tools/typeSpecificationService.js';

function createNullLogger(): winston.Logger {
  return winston.createLogger({ silent: true });
}

describe('TypeSpecificationService', () => {
  let service: TypeSpecificationService;

  beforeEach(() => {
    service = new TypeSpecificationService(createNullLogger());
  });

  describe('search', () => {
    it('should return matches for a pattern', () => {
      const result = service.search('COMPOSITION');
      expect(result).toHaveProperty('items');
      expect(result.items.length).toBeGreaterThan(0);
      expect(result.items[0]).toHaveProperty('name');
      expect(result.items[0]).toHaveProperty('resourceUri');
      expect(result.items[0].resourceUri).toMatch(/^openehr:\/\/spec\/type\//);
    });

    it('should support wildcard pattern', () => {
      const result = service.search('DV_*');
      expect(result.items.every((i) => i.name.startsWith('DV_'))).toBe(true);
    });

    it('should return empty for pattern shorter than 3 chars', () => {
      const result = service.search('DV');
      expect(result.items).toHaveLength(0);
    });

    it('should filter by keyword', () => {
      const withoutKw = service.search('COMPOSITION');
      const withKw = service.search('COMPOSITION', 'uid');
      expect(withKw.items.length).toBeLessThanOrEqual(withoutKw.items.length);
    });

    it('should include component in results', () => {
      const result = service.search('COMPOSITION');
      if (result.items.length > 0) {
        expect(result.items[0]).toHaveProperty('component');
        expect(result.items[0].component).toBeTruthy();
      }
    });
  });

  describe('get', () => {
    it('should retrieve a known type specification', () => {
      const result = service.get('COMPOSITION');
      expect(result).toHaveProperty('name', 'COMPOSITION');
    });

    it('should retrieve with component filter', () => {
      const result = service.get('COMPOSITION', 'RM');
      expect(result).toHaveProperty('name');
    });

    it('should throw when type not found', () => {
      expect(() => service.get('NON_EXISTENT_TYPE')).toThrow();
    });

    it('should throw when name is empty', () => {
      expect(() => service.get('')).toThrow('Name cannot be empty');
    });
  });
});
