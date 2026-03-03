import { readFileSync, existsSync } from 'fs';
import { join } from 'path';
import { APP_DIR } from '../constants.js';
import { SpecificationComponentsCompletionProvider } from '../completionProviders/specificationComponents.js';

export const TYPE_SPEC_DIR = join(APP_DIR, 'resources/bmm');

/**
 * Read an openEHR Type specification in BMM JSON format.
 *
 * URI template: openehr://spec/type/{component}/{name}
 */
export function readTypeSpecification(component: string, name: string): Record<string, unknown> {
  component = component.toUpperCase().trim();
  if (!/^[\w-]+$/.test(component)) {
    throw new Error(`Invalid component: ${component}`);
  }

  name = name.toUpperCase().trim();
  if (!/^[\w-]+$/.test(name)) {
    throw new Error(`Invalid type specification name: ${name}`);
  }

  const path = join(TYPE_SPEC_DIR, component, `${name}.bmm.json`);
  if (!existsSync(path)) {
    throw new Error(`Type specification not found: ${component}/${name}`);
  }

  let json: string;
  try {
    json = readFileSync(path, 'utf-8');
  } catch (e) {
    throw new Error(
      `Unable to read Type specification ${component}/${name} content: ${String(e)}`,
    );
  }

  try {
    return JSON.parse(json) as Record<string, unknown>;
  } catch (e) {
    throw new Error(
      `Unable to decode Type specification ${component}/${name} content: ${String(e)}`,
    );
  }
}

export const specificationComponentsProvider = new SpecificationComponentsCompletionProvider();
