import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

export const APP_NAME = 'openehr-assistant-mcp';
export const APP_TITLE = 'openEHR Assistant';
export const APP_DESCRIPTION =
  'MCP Server to assist with various openEHR specifications or modeling related tasks.';
export const APP_ICON =
  'https://www.cadasto.com/wp-content/uploads/2025/11/cropped-cadasto-favicon-32x32.png';
export const APP_VERSION = '0.14.0';

export const APP_ENV = process.env.APP_ENV ?? 'production';
export const LOG_LEVEL = process.env.LOG_LEVEL ?? 'info';

// APP_DIR = repository root (parent of dist/ or src/)
export const APP_DIR = join(__dirname, '..');
export const APP_RESOURCES_DIR = join(APP_DIR, 'resources');
export const APP_DATA_DIR = join(process.env.XDG_DATA_HOME ?? '/tmp', 'app');

const rawCkmUrl = (
  process.env.CKM_API_BASE_URL ?? 'https://ckm.openehr.org/ckm/rest'
).replace(/[/\s]+$/, '');
export const CKM_API_BASE_URL = rawCkmUrl + '/';

export const HTTP_SSL_VERIFY =
  process.env.HTTP_SSL_VERIFY === undefined
    ? true
    : process.env.HTTP_SSL_VERIFY !== 'false' && process.env.HTTP_SSL_VERIFY !== '0';

export const HTTP_TIMEOUT = (parseFloat(process.env.HTTP_TIMEOUT ?? '10') || 10) * 1000;
