/**
 * Tests for the deterministic comment reflow engine.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { describe, expect, it } from 'vitest';
import { KIND, classify, isFence, listMarker, reflow, tokenize } from '../comment-reflow.js';

const cases = [
    {
        name: 'overflowing prose wraps',
        lines: ['This is a long descriptive prose paragraph inside the docblock that runs comfortably beyond eighty.'],
        expected: ['This is a long descriptive prose paragraph inside the docblock that runs', 'comfortably beyond eighty.'],
        long: [0],
        premature: [],
    },
    {
        name: 'prematurely wrapped prose merges',
        lines: ['This paragraph was wrapped', 'far too early on every', 'single line here.'],
        expected: ['This paragraph was wrapped far too early on every single line here.'],
        long: [],
        premature: [0, 1],
    },
    {
        name: 'canonical prose is untouched',
        lines: ['Keeps standalone comment lines within a readable width. Docblock tag lines', '(@param, @return, ...) are exempt, as is any line whose overflow is a single', 'unbreakable token.'],
        expected: ['Keeps standalone comment lines within a readable width. Docblock tag lines', '(@param, @return, ...) are exempt, as is any line whose overflow is a single', 'unbreakable token.'],
        long: [],
        premature: [],
    },
    {
        name: 'overflow and premature together',
        lines: ['Tiny.', 'This second line of the same paragraph is intentionally written to overflow far past the eighty limit here.'],
        expected: ['Tiny. This second line of the same paragraph is intentionally written to', 'overflow far past the eighty limit here.'],
        long: [1],
        premature: [0],
    },
    {
        name: 'list item keeps hanging indent',
        lines: ['- first item that is quite long and will need to wrap onto a second line with a hanging indent applied', '- second short item'],
        expected: ['- first item that is quite long and will need to wrap onto a second line with', '  a hanging indent applied', '- second short item'],
        long: [0],
        premature: [],
    },
    {
        name: 'ordered list item wraps',
        lines: ['1. an ordered item that also runs long enough that it must wrap onto another line under the number'],
        expected: ['1. an ordered item that also runs long enough that it must wrap onto another', '   line under the number'],
        long: [0],
        premature: [],
    },
    {
        name: 'inline code stays whole',
        lines: ['The value `some spaced code` must never split across the wrap boundary even when it is long here.'],
        expected: ['The value `some spaced code` must never split across the wrap boundary even', 'when it is long here.'],
        long: [0],
        premature: [],
    },
    {
        name: 'bare tag never opens a line',
        lines: ['Prose that mentions tags such as @param and @return which could otherwise start lines here ok yes now.'],
        expected: ['Prose that mentions tags such as @param and @return which could otherwise', 'start lines here ok yes now.'],
        long: [0],
        premature: [],
    },
    {
        name: 'url line is left unwrapped',
        lines: ['See https://example.com/a/very/long/path/that/keeps/going/well/past/the/eighty/character/limit/ok'],
        expected: ['See https://example.com/a/very/long/path/that/keeps/going/well/past/the/eighty/character/limit/ok'],
        long: [],
        premature: [],
    },
    {
        name: 'commented-out code is verbatim',
        lines: ['const x = compute(this.value);'],
        expected: ['const x = compute(this.value);'],
        long: [],
        premature: [],
    },
    {
        name: 'a mid-prose tag glued to the previous word is already canonical',
        lines: ['alpha bravo charlie delta echo foxtrot golf hotel india juliet then', 'check @internal and keep going with lots more trailing words to spill'],
        expected: ['alpha bravo charlie delta echo foxtrot golf hotel india juliet then', 'check @internal and keep going with lots more trailing words to spill'],
        long: [],
        premature: [],
    },
];

describe('reflow', () => {
    it.each(cases)('$name', ({ lines, expected, long, premature }) => {
        const result = reflow(lines, 3, 80);

        expect(result.lines).toEqual(expected);
        expect(result.long).toEqual(long);
        expect(result.premature).toEqual(premature);
    });

    it.each(cases)('$name is idempotent', ({ lines, expected }) => {
        const again = reflow(reflow(lines, 3, 80).lines, 3, 80);

        expect(again.lines).toEqual(expected);
        expect(again.long).toEqual([]);
        expect(again.premature).toEqual([]);
    });

    it('follows the configured maximum length', () => {
        const result = reflow(['alpha beta gamma delta epsilon zeta eta theta iota kappa'], 3, 40);

        expect(result.lines).toEqual(['alpha beta gamma delta epsilon zeta', 'eta theta iota kappa']);
        expect(result.long).toEqual([0]);
    });
});

describe('tokenize', () => {
    it('keeps inline code, link tags and Markdown links whole', () => {
        expect(tokenize('a `b c` d')).toEqual(['a', '`b c`', 'd']);
        expect(tokenize('see {@link Foo bar} now')).toEqual(['see', '{@link Foo bar}', 'now']);
        expect(tokenize('a [text here](https://x) b')).toEqual(['a', '[text here](https://x)', 'b']);
    });

    it('treats an unclosed delimiter as an ordinary character', () => {
        expect(tokenize('a `b c')).toEqual(['a', '`b', 'c']);
        expect(tokenize('a [b] c')).toEqual(['a', '[b]', 'c']);
    });
});

describe('classify', () => {
    it('names each kind of comment line', () => {
        expect(classify('   ', false)).toBe(KIND.BLANK);
        expect(classify('A sentence.', false)).toBe(KIND.PROSE);
        expect(classify('@param x', false)).toBe(KIND.TAG);
        expect(classify('phpcs:ignore Foo.Bar', false)).toBe(KIND.DIRECTIVE);
        expect(classify('| a | b |', false)).toBe(KIND.TABLE);
        expect(classify('======', false)).toBe(KIND.SEPARATOR);
        expect(classify('- item', false)).toBe(KIND.LIST);
        expect(classify('1. item', false)).toBe(KIND.LIST);
        expect(classify('return x;', false)).toBe(KIND.CODE);
        expect(classify('```php', false)).toBe(KIND.FENCE);
        expect(classify('anything at all', true)).toBe(KIND.FENCE);
    });

    it('recognises a fence delimiter', () => {
        expect(isFence('```')).toBe(true);
        expect(isFence(' text ')).toBe(false);
    });
});

describe('listMarker', () => {
    it('parses a marker into its bullet, indent and width', () => {
        expect(listMarker('- item')).toEqual({ marker: '-', indent: 0, width: 2 });
        expect(listMarker('  1. item')).toEqual({ marker: '1.', indent: 2, width: 3 });
        expect(listMarker('not a list')).toBeNull();
    });
});

describe('unicode parity with the PHP engine', () => {
    it('counts code points, so an astral emoji is one column', () => {
        const line = `aaaa ${'\u{1F600}'.repeat(3)} bbbb cccc dddd eeee ffff gggg hhhh iiii jjjj kkkk llll mmmm nnnn oo`;
        const result = reflow([line], 3, 80);

        expect(result.lines).toEqual([line]);
        expect(result.long).toEqual([]);
    });

    it('matches whitespace as ASCII only, so a non-breaking-space line stays prose', () => {
        const result = reflow(['prose line one that is fairly short and mergeable', '\u00a0', 'prose line two also short here'], 3, 80);

        expect(classify('\u00a0', false)).toBe(KIND.PROSE);
        expect(result.premature).toEqual([0, 1]);
        expect(result.lines).toHaveLength(2);
    });
});
