import { describe, it, expect, vi, beforeEach } from 'vitest';
import type { AxiosInstance, AxiosResponse } from 'axios';
import winston from 'winston';
import { CkmClient } from '../../src/apis/ckmClient.js';
import { CkmService } from '../../src/tools/ckmService.js';

function createNullLogger(): winston.Logger {
  return winston.createLogger({ silent: true });
}

function mockResponse(data: unknown, headers: Record<string, string> = {}): AxiosResponse {
  return { data, status: 200, statusText: 'OK', headers, config: {} as AxiosResponse['config'] } as AxiosResponse;
}

describe('CkmService', () => {
  let ckmClient: CkmClient;
  let logger: winston.Logger;

  beforeEach(() => {
    logger = createNullLogger();
    const mockAxios = {
      get: vi.fn(),
      post: vi.fn(),
      request: vi.fn(),
    } as unknown as AxiosInstance;
    ckmClient = new CkmClient(logger, mockAxios);
  });

  describe('archetypeSearch', () => {
    it('should send correct query and decode JSON response', async () => {
      const payload = [
        { resourceMainId: 'openEHR-EHR-OBSERVATION.blood_pressure.v1', cid: '123.45a' },
        { resourceMainId: 'openEHR-EHR-OBSERVATION.body_weight.v1', cid: '678.90b' },
      ];

      vi.spyOn(ckmClient, 'get').mockResolvedValue(mockResponse(payload));

      const svc = new CkmService(ckmClient, logger);
      const result = await svc.archetypeSearch('blood');

      expect(ckmClient.get).toHaveBeenCalledWith(
        'v1/archetypes',
        expect.objectContaining({
          params: expect.objectContaining({ 'search-text': 'blood' }),
        }),
      );
      expect(result).toHaveProperty('items');
      expect(result.items).toHaveLength(2);
      expect(result.items[0]).toHaveProperty('cid', '123.45a');
      expect(result.items[0]).toHaveProperty('archetypeId', 'openEHR-EHR-OBSERVATION.blood_pressure.v1');
    });

    it('should sort by score descending', async () => {
      const payload = [
        { cid: 'low', status: 'INITIAL' },
        { cid: 'high', status: 'PUBLISHED', resourceMainDisplayName: 'blood pressure' },
      ];

      vi.spyOn(ckmClient, 'get').mockResolvedValue(mockResponse(payload));

      const svc = new CkmService(ckmClient, logger);
      const result = await svc.archetypeSearch('blood');
      // Published + name match should score higher
      expect(result.items[0].cid).toBe('high');
    });

    it('should throw on network error', async () => {
      vi.spyOn(ckmClient, 'get').mockRejectedValue(new Error('Network error'));

      const svc = new CkmService(ckmClient, logger);
      await expect(svc.archetypeSearch('blood')).rejects.toThrow(
        'Failed to search for CKM Archetypes',
      );
    });

    it('should use X-Total-Count header for total', async () => {
      vi.spyOn(ckmClient, 'get').mockResolvedValue(
        mockResponse([{ cid: '1' }], { 'x-total-count': '42' }),
      );

      const svc = new CkmService(ckmClient, logger);
      const result = await svc.archetypeSearch('blood');
      expect(result.total).toBe(42);
    });
  });

  describe('archetypeGet', () => {
    it('should resolve archetype-id to CID and return code block', async () => {
      // First call: resolve CID
      vi.spyOn(ckmClient, 'get')
        .mockResolvedValueOnce(mockResponse('1013.1.7850'))
        // Second call: fetch archetype
        .mockResolvedValueOnce(mockResponse('archetype ADL content'));

      const svc = new CkmService(ckmClient, logger);
      const result = await svc.archetypeGet('openEHR-EHR-OBSERVATION.blood_pressure.v1', 'adl');

      expect(result).toContain('```');
      expect(result).toContain('archetype ADL content');
    });

    it('should normalize non-openEHR identifiers to CID', async () => {
      vi.spyOn(ckmClient, 'get').mockResolvedValue(mockResponse('adl content'));

      const svc = new CkmService(ckmClient, logger);
      await svc.archetypeGet('123.45a', 'adl');

      // CID is normalized: non-digits replaced with '-'
      expect(ckmClient.get).toHaveBeenCalledWith(
        expect.stringContaining('123.45-'),
        expect.anything(),
      );
    });

    it('should throw on invalid format', async () => {
      const svc = new CkmService(ckmClient, logger);
      await expect(svc.archetypeGet('123', 'invalid')).rejects.toThrow();
    });
  });

  describe('templateSearch', () => {
    it('should search templates and return metadata', async () => {
      const payload = [{ cid: '1013.26.244', resourceMainDisplayName: 'Vital Signs', status: 'PUBLISHED' }];
      vi.spyOn(ckmClient, 'get').mockResolvedValue(mockResponse(payload));

      const svc = new CkmService(ckmClient, logger);
      const result = await svc.templateSearch('vital');

      expect(result.items).toHaveLength(1);
      expect(result.items[0]).toHaveProperty('cid', '1013.26.244');
    });
  });

  describe('templateGet', () => {
    it('should return template definition in a code block', async () => {
      vi.spyOn(ckmClient, 'get').mockResolvedValue(mockResponse('<opt>content</opt>'));

      const svc = new CkmService(ckmClient, logger);
      const result = await svc.templateGet('my_template', 'opt');

      expect(result).toContain('```');
      expect(result).toContain('<opt>content</opt>');
    });

    it('should throw on invalid template format', async () => {
      const svc = new CkmService(ckmClient, logger);
      await expect(svc.templateGet('123', 'invalid')).rejects.toThrow();
    });
  });
});
