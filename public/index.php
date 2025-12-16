<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Mcp\Capability\Registry\Container;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StdioTransport;
use Mcp\Server\Transport\StreamableHttpTransport;
use Monolog\Handler\StreamHandler;
use Monolog\Level as LogLevel;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Log\LoggerInterface;


try {
    // CLI option parsing (supports: --transport=stdio | --transport stdio)
    $transportOption = '';
    if (PHP_SAPI === 'cli') {
        $argv = $_SERVER['argv'] ?? [];
        for ($i = 0; $i < count($argv); $i++) {
            $arg = (string)($argv[$i] ?? '');
            if (str_starts_with($arg, '--transport=')) {
                $transportOption = substr($arg, strlen('--transport=')) ?: '';
                break;
            }
            if ($arg === '--transport') {
                $transportOption = (string)($argv[$i + 1] ?? '');
                break;
            }
        }
    }

    // Initialize the DI container
    $container = new Container();

    // Initialize logger
    $logger = new Logger(APP_NAME);
    $logger->pushHandler(new StreamHandler('php://stderr', LogLevel::fromName(LOG_LEVEL)));
    $logger->info('Starting ...', [
        'version' => APP_VERSION,
        'env' => APP_ENV,
        'log' => LOG_LEVEL,
    ]);
    $container->set(LoggerInterface::class, $logger);

    // Initialize API clients
    $container->set(CkmClient::class, new CkmClient($logger));

    // Build the server
    $builder = Server::builder()
        ->setServerInfo(APP_TITLE, APP_VERSION, APP_DESCRIPTION)
        ->setDiscovery(APP_DIR, ['src/Prompts', 'src/Tools', 'src/Resources'])
        ->setSession(new FileSessionStore(APP_DATA_DIR . '/sessions'), ttl: 10 * 60)
        ->setProtocolVersion(ProtocolVersion::V2025_03_26)
        ->setContainer($container)
        ->setLogger($logger);
    // add resources
    Guidelines::addResources($builder);

    $server = $builder->build();

    // Determine transport: default to streamable-http; allow CLI override to stdio
    if (strtolower($transportOption) === 'stdio') {
        // Run using stdio transport (blocking loop)
        $logger->info('Using stdio transport as requested by --transport=stdio');
        $transport = new StdioTransport();
        $status = $server->run($transport);
        $logger->info('Server listener stopped gracefully (stdio).', ['status' => $status]);
        exit($status);
    }

    // Create PSR-17 factories and HTTP request
    $psr17Factory = new Psr17Factory();
    $creator = new ServerRequestCreator(
        $psr17Factory,
        $psr17Factory,
        $psr17Factory,
        $psr17Factory
    );
    $request = $creator->fromGlobals();

    // Create the Streamable HTTP transport
    $transport = new StreamableHttpTransport(
        $request,
        $psr17Factory,
        $psr17Factory
    );

    // Run the server and get the response
    /** @var Response $response */
    $response = $server->run($transport);
    $response = $response->withHeader('Access-Control-Expose-Headers', 'Mcp-Session-Id');
    // Emit the response
    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
    $content = $response->getBody()->getContents();
    $logger->debug('Server Responded', ['code' => $response->getStatusCode(), 'payload' => $content]);
    echo $content;

    // finalize
    $logger->info('Server listener stopped gracefully (HTTP).');
    exit(0);

} catch (\Throwable $e) {
    $stderr = fopen('php://stderr', 'w');
    if ($stderr !== false) {
        $message = sprintf(
            "[MCP SERVER CRITICAL ERROR]\nError: %s\nFile: %s:%d\n%s\n",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        fwrite($stderr, $message);
    }
    exit(1);
}
