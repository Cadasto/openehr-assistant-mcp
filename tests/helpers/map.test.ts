import { describe, it, expect } from 'vitest';
import { Map } from '../../src/helpers/map.js';

describe('Map helper', () => {
  describe('contentType', () => {
    it('should map json to application/json', () => {
      expect(Map.contentType('json')).toBe('application/json');
      expect(Map.contentType('application/json')).toBe('application/json');
    });

    it('should map xml to application/xml', () => {
      expect(Map.contentType('xml')).toBe('application/xml');
      expect(Map.contentType('opt')).toBe('application/xml');
      expect(Map.contentType('oet')).toBe('application/xml');
      expect(Map.contentType('mindmap')).toBe('application/xml');
    });

    it('should map adl to text/plain', () => {
      expect(Map.contentType('adl')).toBe('text/plain');
      expect(Map.contentType('text')).toBe('text/plain');
      expect(Map.contentType('aql')).toBe('text/plain');
    });

    it('should be case-insensitive', () => {
      expect(Map.contentType('JSON')).toBe('application/json');
      expect(Map.contentType('ADL')).toBe('text/plain');
    });

    it('should throw for unknown format', () => {
      expect(() => Map.contentType('unknown')).toThrow('Invalid format');
    });
  });

  describe('archetypeFormat', () => {
    it('should accept valid formats', () => {
      expect(Map.archetypeFormat('adl')).toBe('adl');
      expect(Map.archetypeFormat('xml')).toBe('xml');
      expect(Map.archetypeFormat('mindmap')).toBe('mindmap');
    });

    it('should be case-insensitive', () => {
      expect(Map.archetypeFormat('ADL')).toBe('adl');
    });

    it('should throw for invalid format', () => {
      expect(() => Map.archetypeFormat('opt')).toThrow('Invalid archetype format');
    });
  });

  describe('templateFormat', () => {
    it('should accept valid formats', () => {
      expect(Map.templateFormat('opt')).toBe('opt');
      expect(Map.templateFormat('oet')).toBe('oet');
    });

    it('should throw for invalid format', () => {
      expect(() => Map.templateFormat('adl')).toThrow('Invalid template format');
    });
  });

  describe('adlVersion', () => {
    it('should return adl2 for adl2', () => {
      expect(Map.adlVersion('adl2')).toBe('adl2');
    });

    it('should return adl1.4 for adl or adl1.4', () => {
      expect(Map.adlVersion('adl')).toBe('adl1.4');
      expect(Map.adlVersion('adl1.4')).toBe('adl1.4');
    });

    it('should throw for unknown version', () => {
      expect(() => Map.adlVersion('adl3')).toThrow('Invalid ADL type');
    });
  });
});
