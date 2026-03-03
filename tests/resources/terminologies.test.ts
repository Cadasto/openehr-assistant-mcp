import { describe, it, expect, beforeEach } from 'vitest';
import { readTerminologies } from '../../src/resources/terminologies.js';

describe('Terminologies resource', () => {
  it('should return terminology data with codesets and groups', () => {
    const data = readTerminologies();
    expect(data).toHaveProperty('codesets');
    expect(data).toHaveProperty('groups');
    expect(Array.isArray(data.codesets)).toBe(true);
    expect(Array.isArray(data.groups)).toBe(true);
  });

  it('should have non-empty groups', () => {
    const data = readTerminologies();
    expect(data.groups.length).toBeGreaterThan(0);
  });

  it('should have groups with required fields', () => {
    const data = readTerminologies();
    for (const group of data.groups) {
      expect(group).toHaveProperty('name');
      expect(group).toHaveProperty('openehr_id');
      expect(group).toHaveProperty('group');
    }
  });

  it('should include composition_category group', () => {
    const data = readTerminologies();
    const compCat = data.groups.find((g) => g.openehr_id === 'composition_category');
    expect(compCat).toBeTruthy();
    expect(compCat?.group).toHaveProperty('433', 'event');
  });
});
