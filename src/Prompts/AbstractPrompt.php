<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use InvalidArgumentException;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;

abstract readonly class AbstractPrompt
{
    private const SHARED_PROMPT_PATH = '_shared/policy';

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
        $sharedMessages = array_values(array_filter(
            $this->loadPromptFile(self::SHARED_PROMPT_PATH),
            static fn (PromptMessage $message): bool => $message->role === Role::Assistant,
        ));

        if ($sharedMessages === []) {
            throw new InvalidArgumentException('Shared prompt policy must contain an assistant block.');
        }

        return array_merge($sharedMessages, $this->loadPromptFile($name));
    }

    /**
     * @param string $name
     * @return PromptMessage[]
     */
    private function loadPromptFile(string $name): array
    {
        $path = $this->getPromptsDir() . '/' . $name . '.md';

        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('Prompt file not found: %s', $path));
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new InvalidArgumentException(sprintf('Could not read prompt file: %s', $path));
        }

        $messages = [];
        $parts = preg_split('/^## Role: (assistant|user)\b/mi', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false || count($parts) < 2) {
            throw new InvalidArgumentException(sprintf('Invalid prompt file format: %s', $path));
        }

        for ($i = 0; $i < count($parts); $i += 2) {
            $role = trim($parts[$i]);
            $text = trim($parts[$i + 1] ?? '');

            if ($text === '') {
                continue;
            }

            $messages[] = new PromptMessage(Role::from($role), new TextContent($text));
        }

        return $messages;
    }
}
