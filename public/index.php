<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Helpers\CliOptions;
use Cadasto\OpenEHR\MCP\Assistant\Resources\Examples;
use Cadasto\OpenEHR\MCP\Assistant\Resources\Guides;
use Cadasto\OpenEHR\MCP\Assistant\Resources\Terminologies;
use Mcp\Capability\Registry\Container;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\Icon;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\Http\Middleware\ProtocolVersionMiddleware;
use Mcp\Server\Transport\StdioTransport;
use Mcp\Server\Transport\StreamableHttpTransport;
use Monolog\Handler\StreamHandler;
use Monolog\Level as LogLevel;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;


try {
    // CLI option parsing (supports: --transport=stdio | --transport stdio)
    $transportOption = CliOptions::transportOption();

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

    // Initialize API clients, resources, etc.
    $container->set(CkmClient::class, new CkmClient($logger));
    $container->set(Guides::class, new Guides());
    $container->set(Terminologies::class, new Terminologies());

    // Initialize cache (ensure directory exists)
    $cacheDir = APP_DATA_DIR . '/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0775, true);
    }
    $cache = new Psr16Cache(new PhpFilesAdapter('mcp-server', 0, $cacheDir));

    // Load server instructions. Optional at the protocol level, but this server
    // ships a canonical resources/server-instructions.md — a missing/unreadable
    // file is a packaging error, so warn rather than silently advertise none.
    $instructionsPath = APP_DIR . '/resources/server-instructions.md';
    $instructions = is_readable($instructionsPath) ? file_get_contents($instructionsPath) : false;
    if ($instructions === false) {
        $logger->warning('Server instructions unavailable; starting without them.', ['path' => $instructionsPath]);
        $instructions = null;
    }

    // Build the server
    $builder = Server::builder()
        ->setServerInfo(APP_TITLE, APP_VERSION, APP_DESCRIPTION, [new Icon(APP_ICON)])
        ->setDiscovery(APP_DIR, ['src/Prompts', 'src/Tools', 'src/Resources'], cache: $cache)
        // mcp/sdk 0.7.0 makes element loading lazy by default. Force eager
        // loading so a broken capability fails at build() (on every request
        // under php-fpm, and at startup under stdio) rather than on first use —
        // and so the advertised capability set always matches what the registry
        // can actually load (lazy mode can advertise tools it then fails to list).
        ->setLazyLoading(false)
        ->setSession(new FileSessionStore(APP_DATA_DIR . '/sessions', ttl: 10 * 60))
        ->setProtocolVersion(ProtocolVersion::V2025_03_26)
        ->setContainer($container)
        ->setInstructions($instructions)
        ->setLogger($logger);
    // add resources
    Guides::addResources($builder);
    Examples::addResources($builder);

    $server = $builder->build();

    // Determine transport: default to streamable-http; allow CLI override to stdio
    if ($transportOption === 'stdio') {
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

    // Some proxy chains send the Host header twice; PSR-7 joins duplicates with ", ".
    // Collapse to the first value so the DNS-rebinding check sees a clean host.
    $hostLine = $request->getHeaderLine('Host');
    if (str_contains($hostLine, ',')) {
        $request = $request->withHeader('Host', trim(explode(',', $hostLine)[0]));
    }

    // Create the Streamable HTTP transport. SDK >= 0.6 enables CORS, DNS-rebinding,
    // and protocol-version middleware by default; we keep those but configure the
    // DNS-rebinding allow-list from MCP_ALLOWED_HOSTS (the server runs behind a reverse proxy).
    $allowedHosts = array_values(array_filter(array_map('trim', explode(',', MCP_ALLOWED_HOSTS))));

    $transport = new StreamableHttpTransport(
        $request,
        $psr17Factory,
        $psr17Factory,
        $logger,
        [
            new CorsMiddleware(),
            new DnsRebindingProtectionMiddleware($allowedHosts),
            new ProtocolVersionMiddleware(),
        ]
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
    // (string) $e carries the message, file:line, stack trace AND the chained
    // getPrevious() cause. That chain matters now that eager discovery loading
    // (setLazyLoading(false)) surfaces wrapped loader failures here, whose root
    // cause (malformed attribute, reflection error) lives in the previous.
    $message = sprintf("[MCP SERVER CRITICAL ERROR]\n%s\n", (string)$e);
    $stderr = fopen('php://stderr', 'w');
    if ($stderr !== false) {
        fwrite($stderr, $message);
        fclose($stderr);
    } else {
        // stderr unavailable — fall back so the crash is never fully silenced.
        error_log($message);
    }
    exit(1);
}
