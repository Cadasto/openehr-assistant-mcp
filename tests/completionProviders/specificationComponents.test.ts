import { describe, it, expect } from 'vitest';
import { SpecificationComponentsCompletionProvider } from '../../src/completionProviders/specificationComponents.js';

describe('SpecificationComponentsCompletionProvider', () => {
  const provider = new SpecificationComponentsCompletionProvider();

  it('should return completions without filter', () => {
    const completions = provider.getCompletions('');
    expect(Array.isArray(completions)).toBe(true);
    expect(completions.length).toBeGreaterThan(0);
  });

  it('should return known BMM component directories', () => {
    const completions = provider.getCompletions('');
    // Known components: AM, AM2, BASE, LANG, RM, TERM
    expect(completions).toContain('AM');
    expect(completions).toContain('RM');
  });

  it('should filter by prefix', () => {
    const completions = provider.getCompletions('A');
    expect(completions.every((c) => c.startsWith('A'))).toBe(true);
  });

  it('should return empty for non-matching prefix', () => {
    const completions = provider.getCompletions('ZZZ');
    expect(completions).toHaveLength(0);
  });
});
