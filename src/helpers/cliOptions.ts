export type TransportOption = 'stdio' | 'streamable-http' | '';

/**
 * Parses optional CLI option: --transport=stdio|streamable-http (or "--transport stdio").
 * Returns '' when not provided.
 */
export function parseTransportOption(argv: string[] = process.argv.slice(2)): TransportOption {
  const allowed: TransportOption[] = ['stdio', 'streamable-http'];
  let value: string | undefined;

  for (let i = 0; i < argv.length; i++) {
    const arg = argv[i];
    if (arg.startsWith('--transport=')) {
      if (value !== undefined) {
        throw new Error('Invalid --transport option: must be provided at most once.');
      }
      value = arg.slice('--transport='.length);
    } else if (arg === '--transport' && i + 1 < argv.length) {
      if (value !== undefined) {
        throw new Error('Invalid --transport option: must be provided at most once.');
      }
      value = argv[++i];
    }
  }

  if (value === undefined) {
    return '';
  }

  const normalized = value.toLowerCase().trim();
  if (normalized === '') {
    throw new Error('Invalid --transport option: value cannot be empty.');
  }

  if (!allowed.includes(normalized as TransportOption)) {
    throw new Error('Invalid --transport option: expected one of: stdio | streamable-http.');
  }

  return normalized as TransportOption;
}
