import { describe, it, expect } from 'vitest';
import { GuidesCompletionProvider } from '../../src/completionProviders/guides.js';

describe('GuidesCompletionProvider', () => {
  const provider = new GuidesCompletionProvider();

  it('should return completions without filter', () => {
    const completions = provider.getCompletions('');
    expect(Array.isArray(completions)).toBe(true);
    expect(completions.length).toBeGreaterThan(0);
  });

  it('should return only .md filenames without extension', () => {
    const completions = provider.getCompletions('');
    for (const c of completions) {
      expect(c).not.toMatch(/\.md$/);
    }
  });

  it('should filter by prefix', () => {
    const completions = provider.getCompletions('adl');
    expect(completions.every((c) => c.startsWith('adl'))).toBe(true);
  });

  it('should return unique values', () => {
    const completions = provider.getCompletions('');
    const unique = [...new Set(completions)];
    expect(completions.length).toBe(unique.length);
  });

  it('should return empty array for non-matching prefix', () => {
    const completions = provider.getCompletions('zzz_nonexistent_xyz');
    expect(completions).toHaveLength(0);
  });
});
