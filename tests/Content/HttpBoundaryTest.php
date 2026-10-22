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
 * src/Apis/CkmClient. Tests mock that one class, so none of them touches the network.
 * A service that built its own Guzzle client, or read a URL with file_get_contents(),
 * would pass every other test while breaking that promise.
 *
 * The scan works on PHP tokens, not raw text, so comments, docblocks and the inside of
 * strings cannot trip it. It sees names as they are written: a class name held in a
 * variable, a namespace alias, or a URL held in a constant is beyond it, and stays a
 * matter for review.
 */
#[CoversNothing]
final class HttpBoundaryTest extends TestCase
{
    /** The one directory allowed to open a connection. */
    private const BOUNDARY = 'src/Apis/';

    /** Classes that hold or build a connection. Matched whole, ignoring case. */
    private const CONNECTION_CLASSES = [
        'GuzzleHttp\Client',
        'GuzzleHttp\ClientInterface',
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

    /** File functions that fetch over the network when their first argument is a URL. */
    private const URL_READERS = ['file_get_contents', 'fopen', 'readfile', 'file', 'copy'];

    private const URL = '~^(?:https?|ftps?)://~i';

    #[DataProvider('applicationFiles')]
    public function test_boundaries_outbound_network_access_stays_in_src_apis(string $file): void
    {
        $found = array_map(
            static fn (array $hit): string => sprintf('%s:%d %s', $file, $hit['line'], $hit['primitive']),
            self::connectionsIn($this->read($file)),
        );

        $this->assertSame(
            [],
            $found,
            'Only src/Apis/CkmClient may open an outbound connection (ADR-0002), so that tests '
            . 'mock one seam and stay offline. Found: ' . implode('; ', $found),
        );
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
     * Every PHP file under src/ and public/, except those inside the boundary.
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
                if ($file->getExtension() === 'php' && !str_starts_with($relative, self::BOUNDARY)) {
                    $files[$relative] = [$relative];
                }
            }
        }
        ksort($files);

        return $files;
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function snippets(): iterable
    {
        // Each of these can reach the network, and is reported under the name it is written with.
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

        // Each of these only looks like one, and must pass.
        yield 'Guzzle request options' => ['use GuzzleHttp\RequestOptions;', []];
        yield 'PSR-18 client exception' => ['use Psr\Http\Client\ClientExceptionInterface;', []];
        yield 'local file read' => ['file_get_contents($path);', []];
        yield 'standard error stream' => ["fopen('php://stderr', 'w');", []];
        yield 'method of the same name' => ["\$files->copy('https://example.invalid', \$to);", []];
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

        foreach ($tokens as $i => $token) {
            if ($token->is('}')) {
                $groupPrefix = '';
            }
            $before = $tokens[$i - 1] ?? null;
            $after = $tokens[$i + 1] ?? null;
            $isName = $token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED]);
            $isMemberOrDeclaration = $before?->is([
                T_OBJECT_OPERATOR,
                T_NULLSAFE_OBJECT_OPERATOR,
                T_DOUBLE_COLON,
                T_FUNCTION,
                T_CONST,
            ]) === true;
            if (!$isName || $isMemberOrDeclaration) {
                continue;
            }

            // In `use GuzzleHttp\{Client, RequestOptions};` the members are relative to the prefix.
            if ($after?->is(T_NS_SEPARATOR) === true && ($tokens[$i + 2] ?? null)?->is('{') === true) {
                $groupPrefix = $token->text . '\\';
                continue;
            }

            $name = ltrim($groupPrefix . $token->text, '\\');
            if (self::isConnectionClass($name)) {
                $found[] = ['primitive' => $name, 'line' => $token->line];
                continue;
            }

            if ($token->is(T_NAME_QUALIFIED) || $after?->is('(') !== true) {
                continue;
            }
            $function = strtolower($name);
            if (in_array($function, self::CONNECTION_FUNCTIONS, true)) {
                $found[] = ['primitive' => $function . '()', 'line' => $token->line];
                continue;
            }
            if (in_array($function, self::URL_READERS, true)) {
                $argument = self::literalFirstArgument($tokens, $i + 1);
                if (preg_match(self::URL, $argument) === 1) {
                    $found[] = ['primitive' => $function . '() on ' . $argument, 'line' => $token->line];
                }
            }
        }

        return $found;
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
     * The literal text a call's first argument starts with, or '' when it starts with anything else.
     *
     * @param list<PhpToken> $tokens
     */
    private static function literalFirstArgument(array $tokens, int $openParenthesis): string
    {
        $argument = $tokens[$openParenthesis + 1] ?? null;
        if ($argument?->is('"') === true) {
            // An interpolated string, "https://{$host}/…": its first fragment is the next token.
            $argument = $tokens[$openParenthesis + 2] ?? null;
        }

        return $argument?->is([T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE]) === true
            ? trim($argument->text, '\'"')
            : '';
    }

    private function read(string $file): string
    {
        $code = file_get_contents(dirname(__DIR__, 2) . '/' . $file);
        $this->assertIsString($code);

        return $code;
    }
}
