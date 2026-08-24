/**
 * Tests for the file scoping of the base flat config's jsdoc rules.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { Linter } from 'eslint';
import { describe, expect, it } from 'vitest';
import config from '../index.js';

const linter = new Linter({ configType: 'flat' });

// One documented function whose tags carry both a type and a description, so
// every jsdoc rule under test has something to say about it. The source is
// valid in either language, leaving the file extension as the only variable.
const TYPED_TAGS = [
    '/**',
    ' * Adds two numbers.',
    ' *',
    ' * @param {number} augend The number added to.',
    ' * @param {number} addend The number added.',
    ' * @returns {number} The sum of the two.',
    ' */',
    'function add(augend, addend) {',
    '    return augend + addend;',
    '}',
    '',
].join('\n');

// The same function with the types stripped and the descriptions left, which is
// what no-types asks a TypeScript file to become.
const UNTYPED_TAGS = TYPED_TAGS.replace(/ \{number\}/g, '');

// The same tags with the descriptions stripped and the types left, which is
// what a plain-JavaScript codebase looks like where the tag is doing the typing
// rather than the describing.
const DESCRIPTIONLESS_TAGS = TYPED_TAGS
    .replace(' augend The number added to.', ' augend')
    .replace(' addend The number added.', ' addend')
    .replace(' The sum of the two.', '');

/**
 * Lint a source under the base config as the given filename and return the
 * rules that reported, deduplicated.
 */
function report(source, filename) {
    return [...new Set(linter.verify(source, config, filename).map(message => message.ruleId))];
}

describe('jsdoc/no-types', () => {
    it('faults a typed tag in TypeScript, where the signature holds the type', () => {
        for (const filename of ['example.ts', 'example.tsx', 'example.mts', 'example.cts']) {
            expect(report(TYPED_TAGS, filename)).toContain('jsdoc/no-types');
        }
    });

    it('leaves a typed tag alone in JavaScript, where nothing else holds the type', () => {
        for (const filename of ['example.js', 'example.jsx', 'example.mjs', 'example.cjs']) {
            expect(report(TYPED_TAGS, filename)).not.toContain('jsdoc/no-types');
        }
    });

    it('accepts an untyped tag in either language', () => {
        expect(report(UNTYPED_TAGS, 'example.ts')).not.toContain('jsdoc/no-types');
        expect(report(UNTYPED_TAGS, 'example.js')).not.toContain('jsdoc/no-types');
    });
});

describe('jsdoc description rules', () => {
    it('requires a description on every tag in either language, typed or not', () => {
        for (const filename of ['example.ts', 'example.js']) {
            const rules = report(DESCRIPTIONLESS_TAGS, filename);

            expect(rules).toContain('jsdoc/require-param-description');
            expect(rules).toContain('jsdoc/require-returns-description');
        }
    });

    it('accepts a described tag in either language', () => {
        for (const filename of ['example.ts', 'example.js']) {
            const rules = report(TYPED_TAGS, filename);

            expect(rules).not.toContain('jsdoc/require-param-description');
            expect(rules).not.toContain('jsdoc/require-returns-description');
        }
    });
});
