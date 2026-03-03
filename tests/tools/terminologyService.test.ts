import { describe, it, expect, beforeEach } from 'vitest';
import winston from 'winston';
import { TerminologyService } from '../../src/tools/terminologyService.js';

function createNullLogger(): winston.Logger {
  return winston.createLogger({ silent: true });
}

describe('TerminologyService', () => {
  let service: TerminologyService;

  beforeEach(() => {
    service = new TerminologyService(createNullLogger());
  });

  it('should resolve concept ID to rubric', () => {
    const result = service.resolve('433');
    expect(result.id).toBe('433');
    expect(result.rubric).toBe('event');
    expect(result.groupId).toBe('composition_category');
    expect(result.groupName).toBe('composition category');
  });

  it('should resolve rubric to concept ID', () => {
    const result = service.resolve('event');
    expect(result.id).toBe('433');
    expect(result.rubric).toBe('event');
  });

  it('should resolve rubric case-insensitively', () => {
    const result = service.resolve('EVENT');
    expect(result.id).toBe('433');
    expect(result.rubric).toBe('event');
  });

  it('should resolve with group ID restriction', () => {
    const result = service.resolve('433', 'composition_category');
    expect(result.id).toBe('433');
    expect(result.groupId).toBe('composition_category');
  });

  it('should throw when concept not found in specified group', () => {
    expect(() => service.resolve('433', 'attestation_reason')).toThrow(
      'Could not resolve "433" within group "attestation_reason"',
    );
  });

  it('should throw when group not found', () => {
    expect(() => service.resolve('433', 'invalid_group')).toThrow(
      'Terminology group "invalid_group" not found.',
    );
  });

  it('should throw when concept not found at all', () => {
    expect(() => service.resolve('non_existent_rubric')).toThrow(
      'Could not resolve "non_existent_rubric" in openEHR terminology.',
    );
  });

  it('should throw on empty input', () => {
    expect(() => service.resolve('')).toThrow('Input cannot be empty.');
  });

  it('should throw on invalid group ID with special characters', () => {
    expect(() => service.resolve('433', 'invalid group!')).toThrow('Invalid terminology group ID');
  });
});
