/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */
import {expect} from 'chai';
import {
  parseFormat,
  serializeLines,
  resolveToken,
  preferredRaw,
  renderPreview,
  missingRequired,
  placedFieldKeys,
} from '../../../js/pages/country/components/addressFormatModel';

const objects = {
  Customer: ['lastname', 'firstname', 'company', 'vat_number'],
  Warehouse: ['reference', 'name'],
  Country: ['name', 'iso_code'],
  State: ['name', 'iso_code'],
  Address: ['address1', 'address2', 'postcode', 'city', 'phone', 'phone_mobile'],
};

const sampleData = {
  Customer: {firstname: 'John', lastname: 'DOE', company: 'Acme Ltd.'},
  Country: {name: 'France'},
  Address: {address1: '16 Main street', postcode: '75002', city: 'Paris'},
};

describe('addressFormatModel', () => {
  describe('resolveToken', () => {
    it('resolves explicit Object:field', () => {
      const t = resolveToken('Country:name', objects);
      expect(t.object).to.equal('Country');
      expect(t.field).to.equal('name');
      expect(t.raw).to.equal('Country:name');
    });

    it('resolves bare Address fields to Address', () => {
      const t = resolveToken('city', objects);
      expect(t.object).to.equal('Address');
      expect(t.field).to.equal('city');
    });

    it('resolves bare firstname to Customer (whitelist)', () => {
      const t = resolveToken('firstname', objects);
      expect(t.object).to.equal('Customer');
    });

    it('falls back to first matching object for unknown bare tokens', () => {
      // `name` exists on Country, State, Warehouse — picker order has Customer first,
      // but Customer doesn't have `name`, so falls through to Warehouse.
      const t = resolveToken('name', objects);
      expect(t.object).to.equal('Warehouse');
    });
  });

  describe('parseFormat / serializeLines round-trip', () => {
    it('preserves bare tokens (no re-prefixing)', () => {
      const input = 'firstname lastname\naddress1\npostcode city\nCountry:name';
      const lines = parseFormat(input, objects);
      expect(serializeLines(lines)).to.equal(input);
    });

    it('preserves prefixed tokens', () => {
      const input = 'Customer:firstname Customer:lastname\nAddress:city';
      const lines = parseFormat(input, objects);
      expect(serializeLines(lines)).to.equal(input);
    });

    it('normalizes mixed whitespace inside a line', () => {
      const input = 'firstname    lastname';
      const lines = parseFormat(input, objects);
      expect(serializeLines(lines)).to.equal('firstname lastname');
    });

    it('returns an empty array for an empty string', () => {
      expect(parseFormat('', objects)).to.eql([]);
    });
  });

  describe('preferredRaw', () => {
    it('emits the bare form when implicit resolution matches', () => {
      expect(preferredRaw('Customer', 'firstname', objects)).to.equal('firstname');
      expect(preferredRaw('Address', 'city', objects)).to.equal('city');
    });

    it('emits Object:field when implicit resolution would mismatch', () => {
      // `name` resolves to Warehouse implicitly → emit prefixed for Country/State.
      expect(preferredRaw('Country', 'name', objects)).to.equal('Country:name');
      expect(preferredRaw('State', 'name', objects)).to.equal('State:name');
    });
  });

  describe('renderPreview', () => {
    it('substitutes tokens with sample values, joining with spaces', () => {
      const lines = parseFormat('firstname lastname\naddress1\nCountry:name', objects);
      const preview = renderPreview(lines, sampleData);
      expect(preview).to.eql(['John DOE', '16 Main street', 'France']);
    });

    it('skips lines with all-empty values', () => {
      const lines = parseFormat('phone\nCountry:name', objects);
      const preview = renderPreview(lines, sampleData);
      // phone has no sample value → its line is skipped.
      expect(preview).to.eql(['France']);
    });
  });

  describe('missingRequired', () => {
    const required = ['firstname', 'lastname', 'address1', 'city', 'Country:name'];

    it('returns empty when all required are placed', () => {
      const lines = parseFormat('firstname lastname\naddress1 city\nCountry:name', objects);
      expect(missingRequired(lines, required)).to.eql([]);
    });

    it('flags missing bare tokens', () => {
      const lines = parseFormat('lastname\naddress1\ncity\nCountry:name', objects);
      expect(missingRequired(lines, required)).to.eql(['firstname']);
    });

    it('flags missing prefixed tokens', () => {
      const lines = parseFormat('firstname lastname\naddress1\ncity', objects);
      expect(missingRequired(lines, required)).to.eql(['Country:name']);
    });
  });

  describe('placedFieldKeys', () => {
    it('returns Object:field keys for every placed token', () => {
      const lines = parseFormat('firstname\nCountry:name', objects);
      const keys = placedFieldKeys(lines);
      expect(keys.has('Customer:firstname')).to.equal(true);
      expect(keys.has('Country:name')).to.equal(true);
      expect(keys.size).to.equal(2);
    });
  });
});
