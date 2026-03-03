import { randomUUID } from 'crypto';
import express from 'express';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import type { IncomingMessage, ServerResponse } from 'http';

import { APP_VERSION, APP_ENV, LOG_LEVEL } from './constants.js';
import { createLogger } from './logger.js';
import { buildServer } from './server.js';
import { parseTransportOption } from './helpers/cliOptions.js';

async function main(): Promise<void> {
  const logger = createLogger();

  let transportOption: string;
  try {
    transportOption = parseTransportOption();
  } catch (e) {
    process.stderr.write(`[MCP SERVER] CLI error: ${String(e)}\n`);
    process.exit(1);
  }

  logger.info('Starting...', {
    version: APP_VERSION,
    env: APP_ENV,
    log: LOG_LEVEL,
  });

  const server = buildServer(logger);

  if (transportOption === 'stdio') {
    logger.info('Using stdio transport as requested by --transport=stdio');
    const transport = new StdioServerTransport();
    await server.connect(transport);
    logger.info('Server running on stdio transport. Waiting for requests...');
    // Keep the process alive
    await new Promise<void>((resolve) => {
      transport.onclose = resolve;
    });
    logger.info('Server listener stopped gracefully (stdio).');
    process.exit(0);
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Streamable HTTP transport via Express
  // ──────────────────────────────────────────────────────────────────────────

  const app = express();
  app.use(express.json({ limit: '10mb' }));
  app.disable('x-powered-by');

  // Session store: sessionId → transport
  const transports = new Map<string, StreamableHTTPServerTransport>();

  const mcpHandler = async (
    req: express.Request,
    res: express.Response,
  ): Promise<void> => {
    const sessionId = req.headers['mcp-session-id'] as string | undefined;

    // Reuse existing session transport
    if (sessionId && transports.has(sessionId)) {
      const transport = transports.get(sessionId)!;
      await transport.handleRequest(req as IncomingMessage, res as ServerResponse, req.body as unknown);
      return;
    }

    // New session
    const newSessionId = sessionId ?? randomUUID();
    const transport = new StreamableHTTPServerTransport({
      sessionIdGenerator: () => newSessionId,
      onsessioninitialized: (id) => {
        transports.set(id, transport);
        logger.debug('MCP session initialized', { sessionId: id });
      },
    });

    transport.onclose = () => {
      if (transport.sessionId) {
        transports.delete(transport.sessionId);
        logger.debug('MCP session closed', { sessionId: transport.sessionId });
      }
    };

    await server.connect(transport);
    res.setHeader('Access-Control-Expose-Headers', 'Mcp-Session-Id');
    await transport.handleRequest(req as IncomingMessage, res as ServerResponse, req.body as unknown);
  };

  // Route all methods to the MCP handler
  app.all('/', mcpHandler);
  app.all('/mcp', mcpHandler);

  // Simple health check
  app.get('/health', (_req, res) => {
    res.json({ status: 'ok', version: APP_VERSION });
  });

  // Block crawler traffic
  app.get('/robots.txt', (_req, res) => {
    res.type('text/plain').send('User-agent: *\nDisallow: /');
  });

  const port = parseInt(process.env.PORT ?? '3000', 10);
  app.listen(port, () => {
    logger.info(`MCP server listening on HTTP port ${port}`);
  });

  // Graceful shutdown
  const shutdown = async (): Promise<void> => {
    logger.info('Shutting down gracefully...');
    await server.close();
    process.exit(0);
  };

  process.on('SIGTERM', shutdown);
  process.on('SIGINT', shutdown);
}

main().catch((e) => {
  const message = `[MCP SERVER CRITICAL ERROR]\n${String(e)}\n`;
  process.stderr.write(message);
  process.exit(1);
});
