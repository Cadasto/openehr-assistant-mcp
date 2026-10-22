<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Helpers;

use Cadasto\OpenEHR\MCP\Assistant\Helpers\TerminologyXmlLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(TerminologyXmlLoader::class)]
final class TerminologyXmlLoaderTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        // A fresh directory per test, so a path cached by an earlier test is never reused.
        $this->dir = sys_get_temp_dir() . '/terminology-loader-' . bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    public function test_two_paths_give_two_documents(): void
    {
        $first = TerminologyXmlLoader::load($this->terminologyFile('first.xml', 'first_group'));
        $second = TerminologyXmlLoader::load($this->terminologyFile('second.xml', 'second_group'));

        $this->assertNotSame($first, $second);
        $this->assertSame('first_group', (string)$first->group['openehr_id']);
        $this->assertSame('second_group', (string)$second->group['openehr_id']);
    }

    public function test_the_same_path_is_parsed_once(): void
    {
        $path = $this->terminologyFile('terminology.xml', 'some_group');

        $this->assertSame(TerminologyXmlLoader::load($path), TerminologyXmlLoader::load($path));
    }

    public function test_the_bundled_terminology_loads(): void
    {
        $xml = TerminologyXmlLoader::load(APP_RESOURCES_DIR . '/terminology/openehr_terminology.xml');

        $this->assertNotEmpty($xml->xpath('/terminology/group[@openehr_id="composition_category"]'));
    }

    public function test_a_missing_file_is_refused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Terminology file not found or not readable.');

        TerminologyXmlLoader::load($this->dir . '/no-such-file.xml');
    }

    public function test_an_unparseable_file_is_refused(): void
    {
        $path = $this->dir . '/broken.xml';
        file_put_contents($path, 'not xml');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Error parsing terminology XML: String could not be parsed as XML$/');

        TerminologyXmlLoader::load($path);
    }

    public function test_an_unparseable_file_raises_no_php_warning_and_restores_the_libxml_mode(): void
    {
        $path = $this->dir . '/broken.xml';
        file_put_contents($path, 'not xml');
        $callerMode = libxml_use_internal_errors(false);
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        });

        try {
            TerminologyXmlLoader::load($path);
            $this->fail('expected the unparseable file to be refused');
        } catch (RuntimeException) {
            // Refused, as test_an_unparseable_file_is_refused pins.
        } finally {
            restore_error_handler();
            $modeLeftByLoader = libxml_use_internal_errors($callerMode);
        }

        // On the stdio transport a warning printed to stdout would corrupt the JSON-RPC stream.
        $this->assertSame([], $warnings);
        $this->assertFalse($modeLeftByLoader, 'the loader must restore the libxml error mode it found');
    }

    public function test_a_file_without_groups_is_refused(): void
    {
        $path = $this->dir . '/empty.xml';
        file_put_contents($path, '<terminology/>');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No terminology groups found.');

        TerminologyXmlLoader::load($path);
    }

    private function terminologyFile(string $name, string $groupId): string
    {
        $path = $this->dir . '/' . $name;
        file_put_contents($path, sprintf(
            '<terminology><group name="%1$s" openehr_id="%1$s"><concept id="1" rubric="one"/></group></terminology>',
            $groupId,
        ));

        return $path;
    }
}
