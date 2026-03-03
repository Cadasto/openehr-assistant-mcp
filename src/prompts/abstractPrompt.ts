import { readFileSync, existsSync } from 'fs';
import { join } from 'path';
import { APP_RESOURCES_DIR } from '../constants.js';

export interface PromptMessage {
  role: 'user' | 'assistant';
  content: { type: 'text'; text: string };
}

export abstract class AbstractPrompt {
  protected getPromptsDir(): string {
    return join(APP_RESOURCES_DIR, 'prompts');
  }

  protected loadPromptMessages(name: string): PromptMessage[] {
    const path = join(this.getPromptsDir(), `${name}.md`);

    if (!existsSync(path)) {
      throw new Error(`Prompt file not found: ${path}`);
    }

    let content: string;
    try {
      content = readFileSync(path, 'utf-8');
    } catch (e) {
      throw new Error(`Could not read prompt file: ${path}: ${String(e)}`);
    }

    const messages: PromptMessage[] = [];
    // Split on "## Role: assistant" or "## Role: user" markers
    const parts = content.split(/^## Role:\s*(assistant|user)\b/im);

    if (parts.length < 3) {
      throw new Error(`Invalid prompt file format: ${path}`);
    }

    // parts[0] is the content before first marker (ignored)
    // parts[1], parts[2], parts[3], parts[4], ... follow as role, text pairs
    for (let i = 1; i < parts.length; i += 2) {
      const role = parts[i].trim().toLowerCase() as 'user' | 'assistant';
      const text = (parts[i + 1] ?? '').trim();
      if (!text) continue;
      messages.push({ role, content: { type: 'text', text } });
    }

    return messages;
  }
}
