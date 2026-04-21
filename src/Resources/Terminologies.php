<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Resources;

use Cadasto\OpenEHR\MCP\Assistant\Helpers\TerminologyXmlLoader;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Exception\ResourceReadException;
use SimpleXMLElement;

final class Terminologies
{
    public const string FILE_PATH = APP_RESOURCES_DIR . '/terminology/openehr_terminology.xml';

    private function loadXml(): SimpleXMLElement
    {
        try {
            return TerminologyXmlLoader::load(self::FILE_PATH);
        } catch (\Throwable $e) {
            throw new ResourceReadException($e->getMessage());
        }
    }

    /**
     * Read the full openEHR Terminology dataset.
     *
     * openEHR Terminology consists of:
     * - Groups: collections of concept–rubric pairs; groups are identified by an openEHR groupId.
     * - Codesets: standardised enumerations used in openEHR models.
     *
     * @return array<string, mixed>
     *   The full openEHR Terminology dataset.
     */
    #[McpResource(
        uri: 'openehr://terminology',
        name: 'terminology',
        mimeType: 'application/json'
    )]
    public function readAll(): array
    {
        $xml = $this->loadXml();
        $results = ['codesets' => [], 'groups' => []];
        foreach ((array)$xml->xpath('//codeset') as $element) {
            $results['codesets'][] = [
                'name' => (string)$element['name'],
                'issuer' => (string)$element['issuer'],
                'openehr_id' => (string)$element['openehr_id'],
                'external_id' => (string)$element['external_id'],
                'codeset' => array_map(fn($code) => (string)$code['value'], (array)$element->xpath('code')),
            ];
        }
        foreach ((array)$xml->xpath('//group') as $element) {
            $concepts = [];
            foreach ($element->concept as $concept) {
                $concepts[(string)$concept['id']] = (string)$concept['rubric'];
            }
            $results['groups'][] = [
                'name' => (string)$element['name'],
                'openehr_id' => (string)$element['openehr_id'],
                'group' => $concepts,
            ];
        }
        return $results;
    }
}
