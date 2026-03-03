export class Map {
  static contentType(format: string): string {
    switch (format.toLowerCase()) {
      case 'json':
      case 'canonical json':
      case 'application/json':
        return 'application/json';
      case 'web template':
      case 'application/openehr.wt+json':
        return 'application/openehr.wt+json';
      case 'flat':
      case 'application/openehr.wt.flat.schema+json':
        return 'application/openehr.wt.flat.schema+json';
      case 'structured':
      case 'application/openehr.wt.structured.schema+json':
        return 'application/openehr.wt.structured.schema+json';
      case 'xml':
      case 'canonical':
      case 'opt':
      case 'oet':
      case 'mindmap':
      case 'application/xml':
        return 'application/xml';
      case 'adl':
      case 'adl2':
      case 'text':
      case 'aql':
      case 'text/plain':
        return 'text/plain';
      default:
        throw new Error(`Invalid format: ${format}`);
    }
  }

  static adlVersion(type: string): string {
    switch (type.toLowerCase()) {
      case 'adl2':
        return 'adl2';
      case 'adl1.4':
      case 'adl':
        return 'adl1.4';
      default:
        throw new Error(`Invalid ADL type: ${type}`);
    }
  }

  static archetypeFormat(format: string): string {
    const f = format.toLowerCase();
    if (!['adl', 'xml', 'mindmap'].includes(f)) {
      throw new Error(`Invalid archetype format: ${format}`);
    }
    return f;
  }

  static templateFormat(format: string): string {
    const f = format.toLowerCase();
    if (!['opt', 'oet'].includes(f)) {
      throw new Error(`Invalid template format: ${format}`);
    }
    return f;
  }
}
