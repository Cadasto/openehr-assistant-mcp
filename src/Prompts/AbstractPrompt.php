<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use InvalidArgumentException;
use Mcp\Exception\PromptGetException;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;

abstract readonly class AbstractPrompt
{
    private const string SHARED_PROMPT_PATH = 'shared/policy';
    private const string PLACEHOLDER_PATTERN = '/\{\{([a-z0-9_]+)\}\}/';

    protected function getPromptsDir(): string
    {
        return APP_RESOURCES_DIR . '/prompts';
    }

    /**
     * @param string $name
     * @param array<string, mixed> $values Raw argument values as received from the client.
     * @param list<string> $requiredKeys Arguments that must be supplied and non-blank.
     * @return PromptMessage[]
     *
     * @throws PromptGetException If an argument is missing, blank, or not a string.
     */
    protected function loadPromptMessages(string $name, array $values = [], array $requiredKeys = []): array
    {
        $values = $this->normaliseArguments($values, $requiredKeys);

        $sharedMessages = array_values(array_filter(
            $this->loadPromptFile(self::SHARED_PROMPT_PATH),
            static fn (PromptMessage $message): bool => $message->role === Role::User,
        ));

        if ($sharedMessages === []) {
            throw new InvalidArgumentException('Shared prompt policy must contain a user block.');
        }

        $messages = array_merge($sharedMessages, $this->loadPromptFile($name));

        return array_map(
            function (PromptMessage $message) use ($values, $name): PromptMessage {
                return $this->substitutePlaceholders($message, $values, $name);
            },
            $messages,
        );
    }

    /**
     * Validate the raw client arguments and narrow them to a `string` map.
     *
     * Concrete prompts type their `__invoke()` parameters `mixed` rather than `string`
     * so that this method sees what the client actually sent. Two SDK behaviours make
     * that necessary — both of which would otherwise degrade silently:
     *
     * - A `string`-typed parameter is coerced with `(string) $argument`, so an array or
     *   object argument becomes the literal `"Array"` (a PHP warning, not a TypeError).
     *   The model would then be handed the word "Array" in place of the user's artefact.
     * - A required argument the client *omits* aborts inside
     *   `ReferenceHandler::prepareArguments()` with a `RegistryException`, which
     *   `GetPromptHandler` flattens into a generic "Error while handling prompt".
     *   `mixed` allows null, so the argument is bound and the check below can name it.
     *
     * The `required` flag in `prompts/list` is unaffected: `Discoverer` derives it from
     * the absence of a default value, not from the declared type.
     *
     * @param array<string, mixed> $values
     * @param list<string> $requiredKeys
     * @return array<string, string>
     *
     * @throws PromptGetException
     *   Always in preference to another exception type: `GetPromptHandler` preserves the
     *   message of `PromptGetException` (and `PromptNotFoundException`) but replaces
     *   every other throwable with a generic string, hiding which argument was at fault.
     */
    private function normaliseArguments(array $values, array $requiredKeys): array
    {
        $normalised = [];
        foreach ($values as $key => $value) {
            $normalised[$key] = match (true) {
                $value === null => null,
                is_string($value) => $value,
                // A JSON number is losslessly representable as a string, so accept it
                // rather than failing a client that sends `adl_version: 1.4` unquoted.
                is_int($value), is_float($value) => (string) $value,
                // Anything else — notably an array or object — would be coerced by the
                // SDK to the literal "Array", handing the model that word in place of
                // the user's artefact. Reject it by name instead of losing the content.
                default => throw new PromptGetException(sprintf(
                    'Prompt argument "%s" must be a string, %s given.',
                    $key,
                    get_debug_type($value),
                )),
            };
        }

        foreach ($requiredKeys as $key) {
            $value = $normalised[$key] ?? null;
            if ($value === null) {
                throw new PromptGetException(sprintf('Missing required prompt argument: %s', $key));
            }
            if (trim($value) === '') {
                throw new PromptGetException(sprintf('Required prompt argument "%s" must not be blank.', $key));
            }
        }

        return array_map(static fn (?string $value): string => $value ?? '', $normalised);
    }

    /**
     * @param array<string, string> $values
     */
    private function substitutePlaceholders(PromptMessage $message, array $values, string $name): PromptMessage
    {
        if (!$message->content instanceof TextContent) {
            return $message;
        }

        $text = $message->content->text;

        // Collect placeholder names from the template itself, before substitution.
        // `preg_replace_callback` never rescans its own replacements, so validating the
        // template (rather than the substituted output) is what lets a value containing
        // a literal `{{token}}` — a pasted ADL/OET snippet, say — pass through verbatim
        // instead of being rejected as an unresolved placeholder.
        if (preg_match_all(self::PLACEHOLDER_PATTERN, $text, $matches) === false) {
            throw new PromptGetException(sprintf(
                'Failed to scan placeholders in %s: %s',
                $name,
                preg_last_error_msg(),
            ));
        }

        foreach (array_unique($matches[1]) as $placeholder) {
            if (!array_key_exists($placeholder, $values)) {
                throw new PromptGetException(sprintf('Unresolved prompt placeholder "%s" in %s', $placeholder, $name));
            }
        }

        $substituted = preg_replace_callback(
            self::PLACEHOLDER_PATTERN,
            static fn (array $match): string => $values[$match[1]],
            $text,
        );

        if ($substituted === null) {
            // Never ship a half-rendered template: falling back to the raw text would
            // hand the model literal `{{...}}` tokens as though they were user input.
            throw new PromptGetException(sprintf(
                'Failed to substitute placeholders in %s: %s',
                $name,
                preg_last_error_msg(),
            ));
        }

        return new PromptMessage($message->role, new TextContent($substituted));
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
