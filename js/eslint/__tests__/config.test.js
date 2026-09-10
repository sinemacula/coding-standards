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

// A file header in the shape the convention describes: a summary, then the tags
// that annotate it. Stripping the summary leaves the tags-only header the
// description rule exists to catch.
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

// The same tags with the descriptions stripped and the types left, which is
// what a plain-JavaScript codebase looks like where the tag is doing the typing
// rather than the describing.
const DESCRIPTIONLESS_TAGS = TYPED_TAGS
    .replace(' augend The number added to.', ' augend')
    .replace(' addend The number added.', ' addend')
    .replace(' The sum of the two.', '');

// A spec file's furniture: a helper declared beside the test case that uses it.
// The helper is what require-jsdoc reaches in a test file; the arrow passed to
// `it` is a call argument, which none of the rule's contexts match.
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

// The same spec with its helper removed: nothing but the arrows the test
// framework takes as call arguments.
const CASES_ONLY = [
    "describe('build', () => {",
    "    it('builds', () => {",
    '        build([1]);',
    '    });',
    '});',
    '',
].join('\n');

// A documented function whose body is both long enough and nested deeply enough
// to fault the metric rules, which a test body is still exempt from.
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
        // Every function in it is an arrow passed as a call argument, which
        // none of the rule's contexts match, so a spec of test cases alone
        // carries no documentation burden at all.
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
