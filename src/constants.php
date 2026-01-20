<?php /** @noinspection PhpDefineCanBeReplacedWithConstInspection */

define('APP_NAME', 'openehr-assistant-mcp');
define('APP_TITLE', 'openEHR Assistant MCP Server');
define('APP_DESCRIPTION', 'A Model Context Protocol (MCP) Server to assist with various openEHR related tasks and APIs');
define('APP_VERSION', '0.8.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('LOG_LEVEL', getenv('LOG_LEVEL') ?: 'info');

define('APP_DIR', dirname(__DIR__));
define('APP_RESOURCES_DIR', APP_DIR . '/resources');
define('APP_DATA_DIR', (getenv('XDG_DATA_HOME') ?: '/tmp') . '/app');

define('CKM_API_BASE_URL', trim(getenv('CKM_API_BASE_URL') ?: 'https://ckm.openehr.org/ckm/rest', "\/ \t\n\r\0\x0B") . '/');

define('HTTP_SSL_VERIFY', (bool)filter_var((string)getenv('HTTP_SSL_VERIFY'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
define('HTTP_TIMEOUT', (float)getenv('HTTP_TIMEOUT') ?: 10.0);
