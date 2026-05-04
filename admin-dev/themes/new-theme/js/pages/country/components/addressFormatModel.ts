/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

/**
 * Pure helpers for the country address-format builder.
 *
 * The component round-trips a multi-line, space-separated token format
 * (`Object:field` or bare `field`). Bare tokens resolve implicitly to the
 * first object that owns them in the picker order: Address first, then
 * Customer for the four legacy whitelist fields, then any object.
 *
 * Mirrors classes/AddressFormat.php parser semantics so the FE never re-prefixes
 * a user-authored bare token on save (minimizes diff vs legacy data).
 */

export type ObjectKey = string;

export interface Token {
  object: ObjectKey;
  field: string;
  raw: string;
}

export type Line = Token[];

export interface AvailableObjects {
  [object: string]: string[];
}

export interface SampleData {
  [object: string]: { [field: string]: string };
}

const PICKER_ORDER: ObjectKey[] = ['Customer', 'Warehouse', 'Country', 'State', 'Address'];

const BARE_CUSTOMER_FIELDS = new Set(['firstname', 'lastname', 'company', 'vat_number']);

/**
 * Resolve a raw token string to its (object, field) pair.
 * Implicit resolution order matches PrestaShop's legacy parser:
 *   1. explicit "Object:field"
 *   2. Address has the field → Address
 *   3. Customer has it AND it is in the bare-customer whitelist → Customer
 *   4. first object in picker order that owns it
 *   5. fallback: Address (the legacy default tab)
 */
export function resolveToken(raw: string, available: AvailableObjects): Token {
  const trimmed = raw.trim();

  if (trimmed.includes(':')) {
    const [object, field] = trimmed.split(':');

    return {object, field, raw: trimmed};
  }
  const addressFields = available.Address ?? [];

  if (addressFields.includes(trimmed)) {
    return {object: 'Address', field: trimmed, raw: trimmed};
  }
  const customerFields = available.Customer ?? [];

  if (BARE_CUSTOMER_FIELDS.has(trimmed) && customerFields.includes(trimmed)) {
    return {object: 'Customer', field: trimmed, raw: trimmed};
  }
  const matched = PICKER_ORDER.find((obj) => (available[obj] ?? []).includes(trimmed));

  if (matched) {
    return {object: matched, field: trimmed, raw: trimmed};
  }

  return {object: 'Address', field: trimmed, raw: trimmed};
}

/**
 * Choose the wire form for a (object, field) pair.
 * Emits the bare form when its implicit resolution would land on the same
 * object — otherwise emits the prefixed form. Keeps user-authored bare tokens
 * stable on round-trip.
 */
export function preferredRaw(object: ObjectKey, field: string, available: AvailableObjects): string {
  const resolved = resolveToken(field, available);

  if (resolved.object === object) {
    return field;
  }
  return `${object}:${field}`;
}

/**
 * Parse a raw multi-line format into structured lines.
 * Empty lines are preserved as empty arrays (so the visual editor can keep
 * a deliberately blank row the user added).
 */
export function parseFormat(text: string, available: AvailableObjects): Line[] {
  if (text === '') {
    return [];
  }
  return text.split('\n').map((line) => {
    const tokens = line.split(/\s+/).filter(Boolean);

    return tokens.map((t) => resolveToken(t, available));
  });
}

/**
 * Serialize lines back to the wire format. Preserves the user-authored raw
 * for tokens that came from parseFormat — only newly-added tokens use
 * `preferredRaw`.
 */
export function serializeLines(lines: Line[]): string {
  return lines.map((line) => line.map((t) => t.raw).join(' ')).join('\n');
}

/**
 * Render lines into preview strings using sample data. Lines whose tokens
 * all resolve to empty values are skipped.
 */
export function renderPreview(lines: Line[], sample: SampleData): string[] {
  return lines
    .map((line) => line
      .map((t) => sample[t.object]?.[t.field] ?? '')
      .filter((v) => v !== '')
      .join(' '))
    .filter((joined) => joined !== '');
}

/**
 * Required field names that are not yet placed anywhere in `lines`.
 * A required-fields entry like `Country:name` matches a placed `Country:name` token,
 * a bare `name` won't satisfy it (but the legacy required list always uses bare
 * fields except `Country:name`).
 */
export function missingRequired(lines: Line[], requiredFields: string[]): string[] {
  const flatTokens = lines.flat();
  const placedRaw = new Set(flatTokens.map((t) => `${t.object}:${t.field}`));
  const placedFieldOnly = new Set(flatTokens.map((t) => t.field));

  return requiredFields.filter((req) => {
    if (req.includes(':')) {
      return !placedRaw.has(req);
    }
    return !placedFieldOnly.has(req);
  });
}

/**
 * Set of "Object:field" keys already present, for disabling picker pills.
 */
export function placedFieldKeys(lines: Line[]): Set<string> {
  return new Set(lines.flat().map((t) => `${t.object}:${t.field}`));
}
