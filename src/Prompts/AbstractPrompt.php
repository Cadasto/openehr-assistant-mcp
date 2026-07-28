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
    /**
     * Deliberately broader than PLACEHOLDER_PATTERN so that a token the substituter would
     * *not* replace — `{{Audience}}`, `{{adl-text}}`, `{{ adl_text }}` — is still seen and
     * rejected. Matching only the strict form left such tokens invisible to both the
     * substituter and the guard, so they shipped to the client as literal text.
     */
    private const string PLACEHOLDER_SCAN_PATTERN = '/\{\{[^{}]*\}\}/';

    protected function getPromptsDir(): string
    {
        return APP_RESOURCES_DIR . '/prompts';
    }

    /**
     * @param string $name
     * @param array<string, mixed> $values Raw argument values as received from the client.
     * @param list<string> $requiredKeys Arguments that must be supplied and non-blank.
     * @param array<string, list<string>> $vocabularies
     *   Argument name → the closed set of values the prompt body branches on.
     * @param array<string, array<string, list<string>>> $requiredWhen
     *   Argument name → controlling argument → the values that make it mandatory.
     * @return PromptMessage[]
     *
     * @throws PromptGetException
     *   If a required argument is missing or blank, if an argument is neither a string nor
     *   a number, if a value falls outside its declared vocabulary, if a conditionally
     *   required argument is absent, or if the template carries a placeholder that is
     *   malformed or has no matching argument.
     * @throws InvalidArgumentException If the shared policy block contains no user message.
     */
    protected function loadPromptMessages(
        string $name,
        array $values = [],
        array $requiredKeys = [],
        array $vocabularies = [],
        array $requiredWhen = [],
    ): array {
        $values = $this->normaliseArguments($values, $requiredKeys);
        $values = $this->applyVocabularies($values, $vocabularies);
        $this->applyConditionalRequirements($values, $requiredWhen);

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
     * Constrain arguments whose value the prompt body branches on.
     *
     * These tokens gate destructive behaviour — "for review, do not rewrite the artefact" —
     * so an unrecognised value does not degrade gracefully: the branch simply never fires
     * and the model rewrites an artefact the user asked it only to review. Matching is
     * case-insensitive and the value is rewritten to the canonical token, so a client
     * sending `Review` cannot miss a `review` branch on capitalisation alone.
     *
     * Blank values are left to `$requiredKeys` / `$requiredWhen`; an optional argument the
     * client omitted is not a vocabulary violation.
     *
     * @param array<string, string> $values
     * @param array<string, list<string>> $vocabularies
     * @return array<string, string>
     */
    private function applyVocabularies(array $values, array $vocabularies): array
    {
        foreach ($vocabularies as $key => $allowed) {
            $value = trim($values[$key] ?? '');
            if ($value === '') {
                continue;
            }

            $canonical = null;
            foreach ($allowed as $candidate) {
                if (strcasecmp($value, $candidate) === 0) {
                    $canonical = $candidate;
                    break;
                }
            }

            if ($canonical === null) {
                throw new PromptGetException(sprintf(
                    'Prompt argument "%s" must be one of: %s — "%s" given.',
                    $key,
                    implode(' | ', $allowed),
                    $value,
                ));
            }

            $values[$key] = $canonical;
        }

        return $values;
    }

    /**
     * Enforce the "expected when" pairings the prompt body already relies on.
     *
     * `task_type: review` with no artefact supplied renders an instruction to "preserve the
     * supplied Existing Archetype unchanged" directly above an empty slot, and the model
     * confidently reviews nothing. Declaring the pairing turns that into a named error.
     *
     * @param array<string, string> $values
     * @param array<string, array<string, list<string>>> $requiredWhen
     */
    private function applyConditionalRequirements(array $values, array $requiredWhen): void
    {
        foreach ($requiredWhen as $key => $conditions) {
            if (trim($values[$key] ?? '') !== '') {
                continue;
            }

            foreach ($conditions as $controlKey => $triggerValues) {
                $controlValue = trim($values[$controlKey] ?? '');
                foreach ($triggerValues as $trigger) {
                    if (strcasecmp($controlValue, $trigger) === 0) {
                        throw new PromptGetException(sprintf(
                            'Prompt argument "%s" is required when "%s" is "%s".',
                            $key,
                            $controlKey,
                            $trigger,
                        ));
                    }
                }
            }
        }
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

        // Scan the template itself, before substitution. `preg_replace_callback` never
        // rescans its own replacements, so validating the template (rather than the
        // substituted output) is what lets a *value* containing a literal `{{token}}` — a
        // pasted ADL/OET snippet, say — pass through verbatim instead of being rejected as
        // an unresolved placeholder. Templates are repo-controlled, so scanning them
        // strictly costs nothing at runtime.
        if (preg_match_all(self::PLACEHOLDER_SCAN_PATTERN, $text, $matches) === false) {
            throw new PromptGetException(sprintf(
                'Failed to scan placeholders in %s: %s',
                $name,
                preg_last_error_msg(),
            ));
        }

        foreach (array_unique($matches[0]) as $token) {
            // Reject anything the substituter below would silently leave in place. A token
            // outside the strict charset is an authoring mistake, not client input, and
            // shipping it hands the model a literal `{{…}}` where content should be.
            if (preg_match(self::PLACEHOLDER_PATTERN, $token, $strict) !== 1 || $strict[0] !== $token) {
                throw new PromptGetException(sprintf(
                    'Malformed prompt placeholder "%s" in %s: names must be lower_snake_case with no surrounding whitespace.',
                    $token,
                    $name,
                ));
            }

            if (!array_key_exists($strict[1], $values)) {
                throw new PromptGetException(sprintf('Unresolved prompt placeholder "%s" in %s', $strict[1], $name));
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
