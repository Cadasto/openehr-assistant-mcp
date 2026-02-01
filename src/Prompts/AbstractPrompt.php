<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use InvalidArgumentException;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
use Symfony\Component\Yaml\Yaml;

abstract readonly class AbstractPrompt
{
    protected function getPromptsDir(): string
    {
        return APP_RESOURCES_DIR . '/prompts';
    }

    /**
     * @param string $name
     * @return PromptMessage[]
     */
    protected function loadPromptMessages(string $name): array
    {
        $path = $this->getPromptsDir() . '/' . $name . '.yaml';

        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('Prompt file not found: %s', $path));
        }

        $data = Yaml::parseFile($path);

        if (!is_array($data) || !isset($data['messages']) || !is_array($data['messages'])) {
            throw new InvalidArgumentException(sprintf('Prompt file missing messages: %s', $path));
        }

        $messages = [];
        foreach ($data['messages'] as $message) {
            if (!is_array($message) || !isset($message['role'], $message['content'])) {
                throw new InvalidArgumentException(sprintf('Invalid message format in prompt file: %s', $path));
            }
            $messages[] = new PromptMessage(Role::from((string)$message['role']), new TextContent((string)$message['content']));
        }

        return $messages;
    }
}
