<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Resources;

use Cadasto\OpenEHR\MCP\Assistant\CompletionProviders\SpecificationComponents;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;

final class TypeSpecifications
{
    public const string DIR = APP_DIR . '/resources/bmm';

    /**
     * Read an openEHR Type (class) specification in BMM JSON format from the specifications/types resource tree.
     *
     * URI template:
     *  openehr://spec/type/{component}/{name}
     *
     * Examples:
     *  - openehr://spec/type/RM/EVENT_CONTEXT
     *  - openehr://spec/type/RM/COMPOSITION
     *  - openehr://spec/type/AM/ARCHETYPE
     *  - openehr://spec/type/BASE/AUTHORED_RESOURCE
     *
     * @param string $component
     *   The openEHR component name (e.g. `RM`, `AM`, `BASE`, etc.)
     * @param string $name
     *   The openEHR Type name (e.g. `DV_QUANTITY`)
     * @return array<string, mixed>
     *   The openEHR Type as BMM JSON.
     */
    #[McpResourceTemplate(
        uriTemplate: 'openehr://spec/type/{component}/{name}',
        name: 'type_specification',
        description: 'An openEHR Type specification identified by component and type name, expressed in BMM JSON format',
        mimeType: 'application/json'
    )]
    public function read(
        #[CompletionProvider(provider: SpecificationComponents::class)]
        string $component,
        string $name
    ): array
    {
        // Basic input validation
        $component = strtoupper(trim($component));
        if (!\preg_match('/^[\w-]+$/', $component)) {
            throw new \InvalidArgumentException(\sprintf('Invalid component: %s', $component));
        }
        $name = strtoupper(trim($name));
        if (!\preg_match('/^[\w-]+$/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid type specification name: %s', $name));
        }

        $path = self::DIR . "/$component/$name.bmm.json";
        if (!\is_file($path) || !\is_readable($path)) {
            throw new ResourceReadException(\sprintf('Type specification not found: %s/%s', $component, $name));
        }

        $json = \file_get_contents($path)
            ?: throw new ResourceReadException(\sprintf('Unable to read Type specification %s/%s content.', $component, $name));

        return json_decode($json, true)
            ?: throw new ResourceReadException(\sprintf('Unable to decode Type specification %s/%s content.', $component, $name));
    }

}
