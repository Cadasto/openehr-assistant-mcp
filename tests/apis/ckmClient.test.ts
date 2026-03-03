import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios, { type AxiosInstance } from 'axios';
import { CkmClient } from '../../src/apis/ckmClient.js';
import winston from 'winston';

function createNullLogger(): winston.Logger {
  return winston.createLogger({ silent: true });
}

describe('CkmClient', () => {
  let logger: winston.Logger;
  let mockAxios: AxiosInstance;

  beforeEach(() => {
    logger = createNullLogger();
    mockAxios = {
      get: vi.fn(),
      post: vi.fn(),
      request: vi.fn(),
      defaults: {},
    } as unknown as AxiosInstance;
  });

  it('should delegate get() to the injected axios instance', async () => {
    const mockResponse = { status: 200, data: 'ok', headers: {} };
    vi.mocked(mockAxios.get).mockResolvedValue(mockResponse);

    const client = new CkmClient(logger, mockAxios);
    const res = await client.get('v1/archetypes', { params: { q: 'bp' } });

    expect(mockAxios.get).toHaveBeenCalledWith('v1/archetypes', { params: { q: 'bp' } });
    expect(res.status).toBe(200);
  });

  it('should delegate request() to the injected axios instance', async () => {
    const mockResponse = { status: 200, data: [] };
    vi.mocked(mockAxios.request).mockResolvedValue(mockResponse);

    const client = new CkmClient(logger, mockAxios);
    await client.request('GET', 'v1/archetypes', {});

    expect(mockAxios.request).toHaveBeenCalledWith(
      expect.objectContaining({ method: 'GET', url: 'v1/archetypes' }),
    );
  });

  it('should log when client is injected', () => {
    const infoSpy = vi.spyOn(logger, 'info');
    new CkmClient(logger, mockAxios);
    expect(infoSpy).toHaveBeenCalledWith('CKM API client injected.');
  });
});
