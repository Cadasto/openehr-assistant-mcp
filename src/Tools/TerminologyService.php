<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SimpleXMLElement;

readonly final class TerminologyService
{
    public const string FILE_PATH = APP_RESOURCES_DIR . '/terminology/openehr_terminology.xml';
    private static ?SimpleXMLElement $terminologyXml = null;

    public function __construct(
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * Resolve an openEHR Terminology concept ID to its rubric, or find the ID for a given rubric.
     *
     * This tool helps in resolving internal openEHR Terminology identifiers to human-readable labels (rubrics) and vice versa.
     * It searches across all groups in the openEHR terminology.
     * Matching:
     * - If `input` is numeric, it's treated as a concept (ID), and the corresponding rubric is returned.
     * - If `input` is non-numeric, it's treated as a rubric (case-insensitive) and the corresponding ID is returned.
     * - An optional `groupId` can be provided to restrict the search to a specific openEHR Terminology group.
     *
     * @param string $input The concept ID (e.g., "433") or concept rubric (e.g., "event") to resolve.
     * @param string $groupId Optional openEHR terminology group ID (e.g., "composition_category") to restrict the search.
     * @return array<string, string|null> The resolved pair: ['id' => '...', 'rubric' => '...', 'groupId' => '...', 'groupName' => '...']
     * @throws ToolCallException If the input or groupId cannot be resolved, or if the input is missing/invalid.
     */
    #[McpTool(
        name: 'terminology_resolve',
        annotations: new ToolAnnotations(readOnlyHint: true),
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string'],
                'rubric' => ['type' => 'string'],
                'groupId' => ['type' => 'string'],
                'groupName' => ['type' => 'string'],
            ],
        ],
    )]
    public function resolve(string $input, string $groupId = ''): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());

        $input = trim($input);
        $groupId = trim($groupId);

        if ($input === '') {
            throw new ToolCallException('Input cannot be empty.');
        }

        $xml = $this->loadXml();
        $isId = is_numeric($input);

        $xpath = '/terminology/group';
        if ($groupId !== '') {
            if (preg_match('/\W/', $groupId)) {
                throw new ToolCallException('Invalid terminology group ID: ' . $groupId);
            }
            $xpath .= sprintf('[@openehr_id="%s"]', strtolower($groupId));
        }

        $groups = $xml->xpath($xpath);
        if (empty($groups)) {
            throw new ToolCallException(sprintf('Terminology group "%s" not found.', $groupId));
        }

        foreach ($groups as $group) {
            foreach ($group->concept as $concept) {
                $id = (string)$concept['id'];
                $rubric = (string)$concept['rubric'];
                if (($isId && $id === $input) || (!$isId && strcasecmp($rubric, $input) === 0)) {
                    return [
                        'id' => $id,
                        'rubric' => $rubric,
                        'groupId' => (string)$group['openehr_id'],
                        'groupName' => (string)$group['name'],
                    ];
                }
            }
        }

        throw new ToolCallException(sprintf(
            'Could not resolve "%s"%s in openEHR terminology.',
            $input,
            $groupId !== '' ? sprintf(' within group "%s"', $groupId) : ''
        ));
    }

    private function loadXml(): SimpleXMLElement
    {
        if (self::$terminologyXml instanceof SimpleXMLElement) {
            return self::$terminologyXml;
        }

        if (!file_exists(self::FILE_PATH) || !is_readable(self::FILE_PATH)) {
            $this->logger->error('Terminology file not found or not readable.', ['path' => self::FILE_PATH]);
            throw new RuntimeException('Terminology file not found or not readable.');
        }

        $content = file_get_contents(self::FILE_PATH);
        if ($content === false) {
            throw new RuntimeException('Unable to read terminology file.');
        }

        try {
            $xml = new SimpleXMLElement($content);
        } catch (\Exception $e) {
            $this->logger->error('Error parsing terminology XML', ['error' => $e->getMessage()]);
            throw new RuntimeException('Error parsing terminology XML: ' . $e->getMessage());
        }

        $groups = $xml->xpath('/terminology/group');
        if (empty($groups)) {
            $this->logger->error('Terminology does not contains groups.');
            throw new RuntimeException('No terminology groups found.');
        }

        self::$terminologyXml = $xml;
        return self::$terminologyXml;
    }
}
