import axios, { type AxiosInstance, type AxiosResponse, type AxiosRequestConfig } from 'axios';
import https from 'https';
import type { Logger } from 'winston';
import { CKM_API_BASE_URL, HTTP_SSL_VERIFY, HTTP_TIMEOUT } from '../constants.js';

export class CkmClient {
  protected readonly client: AxiosInstance;

  constructor(
    protected readonly logger: Logger,
    client?: AxiosInstance,
  ) {
    if (client) {
      this.client = client;
      this.logger.info('CKM API client injected.');
      return;
    }

    const timeout = Math.max(HTTP_TIMEOUT, 5000);
    this.client = axios.create({
      baseURL: CKM_API_BASE_URL,
      timeout,
      httpsAgent: HTTP_SSL_VERIFY ? undefined : new https.Agent({ rejectUnauthorized: false }),
    });
    this.logger.info('CKM API client built.', {
      baseURL: CKM_API_BASE_URL,
      timeout,
      sslVerify: HTTP_SSL_VERIFY,
    });
  }

  async get(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse> {
    return this.client.get(url, config);
  }

  async post(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<AxiosResponse> {
    return this.client.post(url, data, config);
  }

  async request(method: string, url: string, config?: AxiosRequestConfig): Promise<AxiosResponse> {
    return this.client.request({ method, url, ...config });
  }
}
