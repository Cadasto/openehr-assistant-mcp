<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Content;

use FilesystemIterator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Guards the single outbound-HTTP boundary.
 *
 * The server reaches one external system, the CKM REST API, and only through
 * src/Apis/CkmClient. Tests mock that client, so none of them touches the network. Any other
 * class, a second one under src/Apis/ included, that built its own Guzzle client, found one
 * through PSR-18 discovery, or read a URL with file_get_contents(), would pass every other test
 * while breaking that promise. So every PHP file under src/ and public/ but CkmClient is scanned.
 *
 * The scan works on PHP tokens, not raw text, so comments, docblocks and prose inside strings
 * cannot trip it. It reports:
 * - a connection class, imported, written in full, or named inside a grouped import: a client or
 *   transport, or a class that builds or discovers one (HandlerStack::create(),
 *   Psr18ClientDiscovery::find(), new Psr18Client());
 * - a connection function, called, imported with `use function` under any alias, or named
 *   alone in a string, the way a callable is written (call_user_func('curl_init', …));
 * - a URL reader, called directly or under a `use function` alias, whose URL argument,
 *   first or named, is a literal with an http, https, ftp or ftps scheme anywhere in it, so a
 *   URL inside another stream (compress.zlib://, php://filter) counts too. The literal may
 *   be quoted, binary (b'…'), interpolated ("https://{$host}") or a heredoc.
 *
 * It sees code as written, so the following stay a matter for review:
 * - a class or function name held in a variable or built at run time (new $class, $fn());
 * - a namespace alias (use GuzzleHttp as G; new G\Client());
 * - a URL reader passed as a callable (array_map('file_get_contents', $urls),
 *   file_get_contents(...));
 * - a URL held in a variable or a constant, or built at run time ($scheme . '://…');
 * - writing to a URL (file_put_contents('ftp://…'), the target of copy()) and other functions
 *   or classes that accept one (SplFileObject, simplexml_load_file());
 * - a shell command that reaches the network (shell_exec('curl …'));
 * - an SDK class that builds its own client when none is injected (mcp/sdk OAuth discovery,
 *   HttpTransport).
 */
#[CoversNothing]
final class HttpBoundaryTest extends TestCase
{
    /** The one file allowed to open a connection. */
    private const BOUNDARY = 'src/Apis/CkmClient.php';

    /** Classes that hold, build or discover a connection. Matched whole, ignoring case. */
    private const CONNECTION_CLASSES = [
        'GuzzleHttp\Client',
        'GuzzleHttp\ClientInterface',
        'GuzzleHttp\HandlerStack',
        'Http\Discovery\HttpAsyncClientDiscovery',
        'Http\Discovery\HttpClientDiscovery',
        'Http\Discovery\Psr18Client',
        'Http\Discovery\Psr18ClientDiscovery',
        'Psr\Http\Client\ClientInterface',
        'SoapClient',
    ];

    /** Namespaces whose classes are all transports. */
    private const CONNECTION_NAMESPACES = [
        'GuzzleHttp\Handler\\',
        'Symfony\Component\HttpClient\\',
    ];

    /** Functions that open a socket or send a request, whatever their arguments. */
    private const CONNECTION_FUNCTIONS = [
        'curl_init',
        'curl_exec',
        'curl_multi_init',
        'fsockopen',
        'pfsockopen',
        'stream_socket_client',
        'get_headers',
    ];

    /**
     * File functions that fetch over the network when the argument named here is a URL.
     * In each of them that argument comes first.
     */
    private const URL_READERS = [
        'file_get_contents' => 'filename',
        'fopen' => 'filename',
        'readfile' => 'filename',
        'file' => 'filename',
        'copy' => 'from',
    ];

    /** A network scheme anywhere in the literal, so a URL wrapped in another stream counts. */
    private const URL = '~(?:https?|ftps?)://~i';

    #[DataProvider('applicationFiles')]
    public function test_boundaries_outbound_network_access_stays_in_ckm_client(string $file): void
    {
        $found = array_map(
            static fn (array $hit): string => sprintf('%s:%d %s', $file, $hit['line'], $hit['primitive']),
            self::connectionsIn($this->read($file)),
        );

        $this->assertSame(
            [],
            $found,
            'Outbound network access must go only through src/Apis/CkmClient '
            . '(the constitution, docs/architecture.md § Boundaries; ADR-0002), so that tests mock '
            . 'one seam and stay offline. Found: ' . implode('; ', $found),
        );
    }

    #[DataProvider('exemptions')]
    public function test_the_scan_exempts_only_ckm_client(string $file, bool $scanned): void
    {
        $this->assertSame($scanned, self::isScanned($file));
    }

    public function test_the_scan_finds_the_guzzle_client_in_ckm_client(): void
    {
        $this->assertNotSame(
            [],
            self::connectionsIn($this->read('src/Apis/CkmClient.php')),
            'the scan finds nothing in src/Apis/CkmClient.php, which builds the Guzzle client; '
            . 'a scan that matches nothing would let every other file pass unchecked',
        );
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('snippets')]
    public function test_the_scan_tells_a_connection_from_a_lookalike(string $code, array $expected): void
    {
        $this->assertSame($expected, array_column(self::connectionsIn("<?php\n" . $code), 'primitive'));
    }

    /**
     * Every PHP file under src/ and public/, except src/Apis/CkmClient.php.
     *
     * @return array<string, array{string}>
     */
    public static function applicationFiles(): array
    {
        $root = dirname(__DIR__, 2);
        $files = [];

        foreach (['src', 'public'] as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS),
            );
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                $relative = substr($file->getPathname(), strlen($root) + 1);
                if ($file->getExtension() === 'php' && self::isScanned($relative)) {
                    $files[$relative] = [$relative];
                }
            }
        }
        ksort($files);

        return $files;
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function exemptions(): iterable
    {
        yield 'the client itself' => ['src/Apis/CkmClient.php', false];
        yield 'a second client beside it' => ['src/Apis/OtherClient.php', true];
        yield 'a client in a folder under src/Apis/' => ['src/Apis/Ckm/CkmClient.php', true];
        yield 'a tool service' => ['src/Tools/CkmService.php', true];
        yield 'the entrypoint' => ['public/index.php', true];
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function snippets(): iterable
    {
        // Each of these can reach the network, and is reported under the class or function it reaches.
        yield 'Guzzle client import' => ['use GuzzleHttp\Client;', ['GuzzleHttp\Client']];
        yield 'Guzzle client, fully qualified' => ['new \GuzzleHttp\Client();', ['GuzzleHttp\Client']];
        yield 'Guzzle client in a grouped import' => [
            'use GuzzleHttp\{RequestOptions, Client};',
            ['GuzzleHttp\Client'],
        ];
        yield 'Guzzle client interface' => ['use GuzzleHttp\ClientInterface;', ['GuzzleHttp\ClientInterface']];
        yield 'Guzzle handler' => ['use GuzzleHttp\Handler\CurlHandler;', ['GuzzleHttp\Handler\CurlHandler']];
        yield 'PSR-18 client' => ['use Psr\Http\Client\ClientInterface;', ['Psr\Http\Client\ClientInterface']];
        yield 'Symfony HttpClient' => [
            'Symfony\Component\HttpClient\HttpClient::create();',
            ['Symfony\Component\HttpClient\HttpClient'],
        ];
        yield 'SOAP client' => ['new SoapClient($wsdl);', ['SoapClient']];
        yield 'Guzzle handler stack import' => ['use GuzzleHttp\HandlerStack;', ['GuzzleHttp\HandlerStack']];
        yield 'Guzzle handler stack, fully qualified' => [
            'new \GuzzleHttp\HandlerStack($handler);',
            ['GuzzleHttp\HandlerStack'],
        ];
        yield 'Guzzle handler stack with the default handler' => [
            '\GuzzleHttp\HandlerStack::create();',
            ['GuzzleHttp\HandlerStack'],
        ];
        yield 'discovered PSR-18 client import' => ['use Http\Discovery\Psr18Client;', ['Http\Discovery\Psr18Client']];
        yield 'discovered PSR-18 client, fully qualified' => [
            'new \Http\Discovery\Psr18Client();',
            ['Http\Discovery\Psr18Client'],
        ];
        yield 'PSR-18 client discovery import' => [
            'use Http\Discovery\Psr18ClientDiscovery;',
            ['Http\Discovery\Psr18ClientDiscovery'],
        ];
        yield 'PSR-18 client discovery, found' => [
            '\Http\Discovery\Psr18ClientDiscovery::find();',
            ['Http\Discovery\Psr18ClientDiscovery'],
        ];
        yield 'PSR-18 client discovery in a grouped import' => [
            'use Http\Discovery\{Psr17FactoryDiscovery, Psr18ClientDiscovery};',
            ['Http\Discovery\Psr18ClientDiscovery'],
        ];
        yield 'HTTPlug client discovery import' => [
            'use Http\Discovery\HttpClientDiscovery;',
            ['Http\Discovery\HttpClientDiscovery'],
        ];
        yield 'HTTPlug client discovery, found' => [
            'Http\Discovery\HttpClientDiscovery::find();',
            ['Http\Discovery\HttpClientDiscovery'],
        ];
        yield 'HTTPlug async client discovery import' => [
            'use Http\Discovery\HttpAsyncClientDiscovery;',
            ['Http\Discovery\HttpAsyncClientDiscovery'],
        ];
        yield 'HTTPlug async client discovery, found' => [
            '\Http\Discovery\HttpAsyncClientDiscovery::find();',
            ['Http\Discovery\HttpAsyncClientDiscovery'],
        ];
        yield 'curl_init' => ['curl_init($url);', ['curl_init()']];
        yield 'curl_exec, upper case' => ['CURL_EXEC($handle);', ['curl_exec()']];
        yield 'curl_multi_init, fully qualified' => ['\curl_multi_init();', ['curl_multi_init()']];
        yield 'fsockopen' => ['fsockopen($host, 443);', ['fsockopen()']];
        yield 'pfsockopen' => ['pfsockopen($host, 443);', ['pfsockopen()']];
        yield 'stream_socket_client' => ['stream_socket_client($address);', ['stream_socket_client()']];
        yield 'get_headers' => ['get_headers($url);', ['get_headers()']];
        yield 'file_get_contents on https' => [
            "file_get_contents('https://example.invalid');",
            ['file_get_contents() on https://example.invalid'],
        ];
        yield 'fopen on http' => ["fopen('http://example.invalid', 'r');", ['fopen() on http://example.invalid']];
        yield 'readfile on ftp' => ["readfile('ftp://example.invalid/a');", ['readfile() on ftp://example.invalid/a']];
        yield 'file on an interpolated URL' => ['file("https://{$host}/a");', ['file() on https://']];
        yield 'copy from an upper-case scheme' => [
            "copy('HTTPS://example.invalid', \$to);",
            ['copy() on HTTPS://example.invalid'],
        ];
        yield 'curl_init imported under an alias' => [
            "use function curl_init as openHandle;\nopenHandle('https://example.invalid');",
            ['curl_init()'],
        ];
        yield 'curl_init imported fully qualified' => ['use function \curl_init as c2;', ['curl_init()']];
        yield 'file_get_contents imported under an alias' => [
            "use function file_get_contents as fetch;\nfetch('https://example.invalid');",
            ['file_get_contents() on https://example.invalid'],
        ];
        yield 'two functions in one import' => [
            "use function strlen, fsockopen, file as lines;\nlines('ftp://example.invalid');",
            ['fsockopen()', 'file() on ftp://example.invalid'],
        ];
        yield 'file_get_contents with a named argument' => [
            "file_get_contents(filename: 'https://example.invalid');",
            ['file_get_contents() on https://example.invalid'],
        ];
        yield 'fopen with the URL named after another argument' => [
            "fopen(mode: 'r', filename: 'https://example.invalid');",
            ['fopen() on https://example.invalid'],
        ];
        yield 'copy with the source named' => [
            "copy(to: \$to, from: 'https://example.invalid');",
            ['copy() on https://example.invalid'],
        ];
        yield 'URL inside a compression wrapper' => [
            "file_get_contents('compress.zlib://https://example.invalid');",
            ['file_get_contents() on compress.zlib://https://example.invalid'],
        ];
        yield 'URL inside a php filter' => [
            "readfile('php://filter/resource=https://example.invalid');",
            ['readfile() on php://filter/resource=https://example.invalid'],
        ];
        yield 'binary string' => [
            "file_get_contents(b'https://example.invalid');",
            ['file_get_contents() on https://example.invalid'],
        ];
        yield 'binary interpolated string' => ['fopen(b"https://{$host}/a", \'r\');', ['fopen() on https://']];
        yield 'heredoc' => [
            "file_get_contents(<<<URL\n    https://example.invalid\n    URL);",
            ['file_get_contents() on https://example.invalid'],
        ];
        yield 'curl_init named in a callable string' => ["call_user_func('curl_init', \$url);", ['curl_init()']];
        yield 'curl_exec named fully qualified in a callable string' => [
            "\$send = '\\CURL_EXEC';",
            ['curl_exec()'],
        ];
        // A grouped import's prefix ends at its `}`, so a call after it keeps its own name.
        yield 'curl_init after a grouped import' => ["use Foo\\{Bar};\ncurl_init(\$u);", ['curl_init()']];
        // A `use function` list ends at its `;`, so a later comma does not start an import.
        yield 'URL reader after a comma, past a function import' => [
            "use function strlen;\nfoo(\$a, file_get_contents('https://example.invalid'));",
            ['file_get_contents() on https://example.invalid'],
        ];

        // Each of these only looks like one, and must pass.
        yield 'Guzzle request options' => ['use GuzzleHttp\RequestOptions;', []];
        yield 'PSR-18 client exception' => ['use Psr\Http\Client\ClientExceptionInterface;', []];
        yield 'PSR-17 factory discovery' => ['\Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();', []];
        yield 'local file read' => ['file_get_contents($path);', []];
        yield 'local file read, named' => ['file_get_contents(filename: $path);', []];
        yield 'standard error stream' => ["fopen('php://stderr', 'w');", []];
        yield 'php filter on a local file' => ["readfile('php://filter/resource=/tmp/a');", []];
        yield 'method of the same name' => ["\$files->copy('https://example.invalid', \$to);", []];
        yield 'function declared with the same name' => ['function copy(string $from, string $to): void {}', []];
        yield 'named argument that shares a name' => ["\$this->open(file: 'https://example.invalid');", []];
        yield 'harmless function imported under an alias' => [
            "use function strlen as fetch;\nfetch('https://example.invalid');",
            [],
        ];
        // A grouped import always has a namespace prefix, so it never names a global function.
        yield 'grouped function import' => [
            "use function Foo\\{curl_init, file_get_contents as fetch};\nfetch('https://example.invalid');",
            [],
        ];
        yield 'line comment' => ['// curl_init($url); new \GuzzleHttp\Client();', []];
        yield 'docblock' => ['/** @see \GuzzleHttp\Client, curl_init() */', []];
        yield 'string literal' => ["\$hint = 'call curl_init() or GuzzleHttp\\Client';", []];
    }

    /**
     * Every reference in the code that can open a network connection, with its line.
     *
     * @return list<array{primitive: string, line: int}>
     */
    private static function connectionsIn(string $code): array
    {
        $tokens = array_values(array_filter(
            PhpToken::tokenize($code),
            static fn (PhpToken $token): bool => !$token->isIgnorable(),
        ));
        $found = [];
        $groupPrefix = '';
        $inFunctionImport = false;
        /** @var array<string, string> $readerAliases a `use function` alias => the URL reader it names */
        $readerAliases = [];

        foreach ($tokens as $i => $token) {
            if ($token->is('}')) {
                $groupPrefix = '';
            }
            if ($token->is(';')) {
                $inFunctionImport = false;
            }
            $before = $tokens[$i - 1] ?? null;
            $after = $tokens[$i + 1] ?? null;

            // After `use function` come imported names, not a declaration.
            if ($token->is(T_FUNCTION) && $before?->is(T_USE) === true) {
                $inFunctionImport = true;
                continue;
            }

            // A string that holds only a function's name is how a callable is written.
            if ($token->is(T_CONSTANT_ENCAPSED_STRING)) {
                $callable = strtolower(ltrim(self::literalText($token), '\\'));
                if (in_array($callable, self::CONNECTION_FUNCTIONS, true)) {
                    $found[] = ['primitive' => $callable . '()', 'line' => $token->line];
                }
                continue;
            }

            $isName = $token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED]);
            $isImportedFunction = $inFunctionImport && $before?->is([T_FUNCTION, ',']) === true;
            $isMemberOrDeclaration = $before?->is([
                T_OBJECT_OPERATOR,
                T_NULLSAFE_OBJECT_OPERATOR,
                T_DOUBLE_COLON,
                T_FUNCTION,
                T_CONST,
            ]) === true;
            if (!$isName || ($isMemberOrDeclaration && !$isImportedFunction)) {
                continue;
            }

            // In `use GuzzleHttp\{Client, RequestOptions};` the members are relative to the prefix.
            if ($after?->is(T_NS_SEPARATOR) === true && ($tokens[$i + 2] ?? null)?->is('{') === true) {
                $groupPrefix = $token->text . '\\';
                continue;
            }

            $name = ltrim($groupPrefix . $token->text, '\\');
            if ($isImportedFunction) {
                // `use function curl_init as open;` is reported where it is imported; a URL
                // reader's alias is remembered, so each call through it is checked like the reader.
                $function = strtolower($name);
                $alias = $after?->is(T_AS) === true ? ($tokens[$i + 2] ?? null) : null;
                if (in_array($function, self::CONNECTION_FUNCTIONS, true)) {
                    $found[] = ['primitive' => $function . '()', 'line' => $token->line];
                } elseif (isset(self::URL_READERS[$function]) && $alias !== null) {
                    $readerAliases[strtolower($alias->text)] = $function;
                }
                continue;
            }

            if (self::isConnectionClass($name)) {
                $found[] = ['primitive' => $name, 'line' => $token->line];
                continue;
            }

            if ($token->is(T_NAME_QUALIFIED) || $after?->is('(') !== true) {
                continue;
            }
            $function = strtolower($name);
            if ($token->is(T_STRING)) {
                $function = $readerAliases[$function] ?? $function;
            }
            if (in_array($function, self::CONNECTION_FUNCTIONS, true)) {
                $found[] = ['primitive' => $function . '()', 'line' => $token->line];
                continue;
            }
            if (isset(self::URL_READERS[$function])) {
                $argument = self::literalArgument($tokens, $i + 1, self::URL_READERS[$function]);
                if (preg_match(self::URL, $argument) === 1) {
                    $found[] = ['primitive' => $function . '() on ' . $argument, 'line' => $token->line];
                }
            }
        }

        return $found;
    }

    /** Whether the boundary test scans the file at this path, relative to the repository root. */
    private static function isScanned(string $relative): bool
    {
        return $relative !== self::BOUNDARY;
    }

    private static function isConnectionClass(string $name): bool
    {
        $name = strtolower($name);

        foreach (self::CONNECTION_CLASSES as $class) {
            if ($name === strtolower($class)) {
                return true;
            }
        }
        foreach (self::CONNECTION_NAMESPACES as $namespace) {
            if (str_starts_with($name . '\\', strtolower($namespace))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The literal text a call's argument starts with, or '' when it starts with anything else.
     * The argument is the first one, or the one named $parameter: `fopen(mode: 'r', filename: …)`.
     *
     * @param list<PhpToken> $tokens
     */
    private static function literalArgument(array $tokens, int $openParenthesis, string $parameter): string
    {
        $start = self::argumentStart($tokens, $openParenthesis, $parameter);
        $argument = $start === null ? null : $tokens[$start] ?? null;
        if ($argument !== null && ($argument->id === ord('"') || $argument->is(T_START_HEREDOC))) {
            // "https://{$host}/…", b"…" or a heredoc: the first fragment is the next token.
            $argument = $tokens[$start + 1] ?? null;
        }

        return $argument?->is([T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE]) === true
            ? self::literalText($argument)
            : '';
    }

    /**
     * Where the value of the first argument, or of the argument named $parameter, begins.
     *
     * @param list<PhpToken> $tokens
     */
    private static function argumentStart(array $tokens, int $openParenthesis, string $parameter): ?int
    {
        $depth = 0;
        for ($i = $openParenthesis; isset($tokens[$i]); $i++) {
            $token = $tokens[$i];
            if ($token->is(['(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, T_ATTRIBUTE])) {
                $depth++;
            } elseif ($token->is([')', ']', '}'])) {
                $depth--;
            }
            if ($depth === 0) {
                return null;
            }
            if ($i !== $openParenthesis && ($depth !== 1 || !$token->is(','))) {
                continue;
            }

            // An argument begins at $i + 1; `name:` before it makes it a named one.
            $name = $tokens[$i + 1] ?? null;
            $isNamed = $name !== null && $name->is(T_STRING) && ($tokens[$i + 2] ?? null)?->is(':') === true;
            if ($isNamed && $name->text === $parameter) {
                return $i + 3;
            }
            if (!$isNamed && $i === $openParenthesis) {
                return $i + 1;
            }
        }

        return null;
    }

    /** What a string literal or a fragment of one holds, without quotes, a b prefix or edge whitespace. */
    private static function literalText(PhpToken $literal): string
    {
        return $literal->is(T_CONSTANT_ENCAPSED_STRING)
            ? substr(ltrim($literal->text, 'bB'), 1, -1)
            : trim($literal->text);
    }

    private function read(string $file): string
    {
        $code = file_get_contents(dirname(__DIR__, 2) . '/' . $file);
        $this->assertIsString($code);

        return $code;
    }
}
