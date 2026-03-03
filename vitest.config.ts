import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    globals: true,
    environment: 'node',
    include: ['tests/**/*.test.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html'],
      include: ['src/**/*.ts'],
      exclude: ['src/index.ts'],
    },
    env: {
      APP_ENV: 'testing',
      LOG_LEVEL: 'error',
    },
  },
  resolve: {
    alias: {
      '#src': new URL('./src/', import.meta.url).pathname,
    },
  },
});
