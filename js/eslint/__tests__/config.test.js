/**
 * Tests for the file scoping of the base flat config's documentation rules.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { Linter } from 'eslint';
import { describe, expect, it } from 'vitest';
import config from '../index.js';

const linter = new Linter({ configType: 'flat' });

// Valid in either language, so the file extension is the only variable.
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

const UNTYPED_TAGS = TYPED_TAGS.replace(/ \{number\}/g, '');

const DESCRIBED_HEADER = [
    '/**',
    ' * Adds numbers, and says so.',
    ' *',
    ' * @author      Y',
    ' * @copyright   2026 X',
    ' */',
    '',
].join('\n');

const TAGS_ONLY_HEADER = DESCRIBED_HEADER.replace(' * Adds numbers, and says so.\n *\n', '');

const DESCRIPTIONLESS_TAGS = TYPED_TAGS
    .replace(' augend The number added to.', ' augend')
    .replace(' addend The number added.', ' addend')
    .replace(' The sum of the two.', '');

const SPEC = [
    "describe('build', () => {",
    '    function build(values) {',
    '        return values;',
    '    }',
    '',
    "    it('builds', () => {",
    '        build([1]);',
    '    });',
    '});',
    '',
].join('\n');

const CASES_ONLY = [
    "describe('build', () => {",
    "    it('builds', () => {",
    '        build([1]);',
    '    });',
    '});',
    '',
].join('\n');

const OVERSIZED = [
    '/** Runs a scenario. */',
    'function scenario(a, b, c, d, e) {',
    '    if (a) {',
    '        if (b) {',
    '            if (c) {',
    '                if (d) {',
    '                    if (e) {',
    '                        e();',
    '                    }',
    '                }',
    '            }',
    '        }',
    '    }',
    ...Array.from({ length: 50 }, (_, index) => `    e(${index});`),
    '}',
    '',
].join('\n');

/** The distinct rules that reported on a source linted as the given filename. */
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

/** The lines one rule reported on, in the order the linter returned them. */
function linesReported(source, filename, ruleId) {
    return linter.verify(source, config, filename)
        .filter(message => message.ruleId === ruleId)
        .map(message => message.line);
}

describe('jsdoc/require-jsdoc in test files', () => {
    it('requires a documentation comment on a helper declared in a spec', () => {
        for (const filename of ['build.spec.ts', 'build.test.js', 'src/__tests__/support.ts']) {
            expect(linesReported(SPEC, filename, 'jsdoc/require-jsdoc')).toEqual([2]);
        }
    });

    it('holds a helper in a spec to the same bar as one in source', () => {
        expect(linesReported(SPEC, 'build.spec.ts', 'jsdoc/require-jsdoc'))
            .toEqual(linesReported(SPEC, 'build.ts', 'jsdoc/require-jsdoc'));
    });

    it('asks nothing of a spec that declares no helper of its own', () => {
        expect(linesReported(CASES_ONLY, 'build.spec.ts', 'jsdoc/require-jsdoc')).toEqual([]);
    });
});

describe('metric rules in test files', () => {
    it('faults an over-long, deeply nested function in source', () => {
        const rules = report(OVERSIZED, 'scenario.ts');

        expect(rules).toContain('max-lines-per-function');
        expect(rules).toContain('max-depth');
    });

    it('keeps both exemptions a test body relies on', () => {
        for (const filename of ['scenario.spec.ts', 'src/__tests__/support.ts', 'src/tests/support.ts']) {
            const rules = report(OVERSIZED, filename);

            expect(rules).not.toContain('max-lines-per-function');
            expect(rules).not.toContain('max-depth');
        }
    });
});

describe('@sinemacula/require-file-description', () => {
    it('faults a header that carries the tags and no summary', () => {
        for (const filename of ['example.ts', 'example.js']) {
            expect(report(TAGS_ONLY_HEADER + TYPED_TAGS, filename))
                .toContain('@sinemacula/require-file-description');
        }
    });

    it('holds test files to the same header as source files', () => {
        for (const filename of ['example.spec.ts', 'example.test.js', 'src/__tests__/support.ts']) {
            expect(report(TAGS_ONLY_HEADER + TYPED_TAGS, filename))
                .toContain('@sinemacula/require-file-description');
        }
    });

    it('accepts a header whose tags sit below a summary', () => {
        for (const filename of ['example.ts', 'example.js', 'example.spec.ts']) {
            expect(report(DESCRIBED_HEADER + TYPED_TAGS, filename))
                .not.toContain('@sinemacula/require-file-description');
        }
    });
});
