import winston from 'winston';
import { APP_NAME, LOG_LEVEL } from './constants.js';

export function createLogger(): winston.Logger {
  return winston.createLogger({
    level: LOG_LEVEL,
    format: winston.format.combine(
      winston.format.timestamp(),
      winston.format.errors({ stack: true }),
      winston.format.json(),
    ),
    defaultMeta: { service: APP_NAME },
    transports: [
      new winston.transports.Stream({
        stream: process.stderr,
      }),
    ],
  });
}
