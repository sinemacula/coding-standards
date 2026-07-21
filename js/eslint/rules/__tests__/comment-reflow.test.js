/**
 * Tests for the deterministic comment reflow engine.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { describe, expect, it } from 'vitest';
import { bracketDelta, indentWidth, indented, isTypeBoundary, typeTagDepth } from '../comment-classifier.js';
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
    {
        name: 'prose around a fenced block wraps but the fence is verbatim',
        lines: [
            'Some intro prose that is long enough to wrap onto a second line here for the test yes.',
            '',
            '```',
            'code inside the fence that is very long and must never be reflowed at all no matter how long',
            '```',
            'Trailing prose after the fence that is also long enough to wrap onto a second line here now.',
        ],
        expected: [
            'Some intro prose that is long enough to wrap onto a second line here for the',
            'test yes.',
            '',
            '```',
            'code inside the fence that is very long and must never be reflowed at all no matter how long',
            '```',
            'Trailing prose after the fence that is also long enough to wrap onto a second',
            'line here now.',
        ],
        long: [0, 5],
        premature: [],
    },
    {
        name: 'a paragraph that would re-classify once wrapped is left verbatim',
        lines: ['alpha bravo charlie delta echo foxtrot golf hotel india juliet kilo lima mike |foo and more words'],
        expected: ['alpha bravo charlie delta echo foxtrot golf hotel india juliet kilo lima mike |foo and more words'],
        long: [],
        premature: [],
    },
    {
        name: 'prose wrapping with a keyword at the line start still reflows',
        lines: ['A description paragraph that is long enough to wrap onto a second line here for the test to exercise it now.'],
        expected: ['A description paragraph that is long enough to wrap onto a second line here', 'for the test to exercise it now.'],
        long: [0],
        premature: [],
    },
    {
        name: 'a heading is a hard break and the line after it is not pulled up',
        lines: ['### Relation existence', 'Bare array form is the short one here now.'],
        expected: ['### Relation existence', 'Bare array form is the short one here now.'],
        long: [],
        premature: [],
    },
    {
        name: 'an indented command block is left verbatim, not reflowed as prose',
        lines: ['Run the two commands in order:', '', '  node gen.mjs > runtime-env.json', '  aws s3 cp runtime-env.json "s3://a-very-long-bucket-name/path/env.json" --cache-control no-store'],
        expected: ['Run the two commands in order:', '', '  node gen.mjs > runtime-env.json', '  aws s3 cp runtime-env.json "s3://a-very-long-bucket-name/path/env.json" --cache-control no-store'],
        long: [],
        premature: [],
    },
    {
        name: 'a multi-line type-annotation value is left verbatim from opener to close',
        lines: ['@phpstan-type ServiceHooks array{', '    authorize: \\Closure(): void,', '    concerns: array<int, class-string<X>>,', '}'],
        expected: ['@phpstan-type ServiceHooks array{', '    authorize: \\Closure(): void,', '    concerns: array<int, class-string<X>>,', '}'],
        long: [],
        premature: [],
    },
    {
        name: 'a flush type continuation that reads as prose is still left verbatim',
        lines: ['@typedef {{', 'id: number,', 'name: string,', '}} Record'],
        expected: ['@typedef {{', 'id: number,', 'name: string,', '}} Record'],
        long: [],
        premature: [],
    },
    {
        name: 'an indented command directly under a list item stays verbatim',
        lines: ['- Build the container image for production before you deploy it', '      docker build -t app:prod . \\', '          --no-cache'],
        expected: ['- Build the container image for production before you deploy it', '      docker build -t app:prod . \\', '          --no-cache'],
        long: [],
        premature: [],
    },
    {
        name: 'a tab-indented command block stays verbatim',
        lines: ['Run:', '', '\tnpm install && npm run build with a very long tail that exceeds the eighty limit yes'],
        expected: ['Run:', '', '\tnpm install && npm run build with a very long tail that exceeds the eighty limit yes'],
        long: [],
        premature: [],
    },
    {
        name: 'a psalm-type flush shape is left verbatim',
        lines: ['@psalm-type UserRow array{', 'id: int,', 'name: string,', '}'],
        expected: ['@psalm-type UserRow array{', 'id: int,', 'name: string,', '}'],
        long: [],
        premature: [],
    },
    {
        name: 'a stray bracket in a tag description does not freeze the following prose',
        lines: ['@return bool True if a<b for the pair', '', 'This trailing paragraph is genuinely long enough that it should be reflowed by the engine now indeed.'],
        expected: ['@return bool True if a<b for the pair', '', 'This trailing paragraph is genuinely long enough that it should be reflowed', 'by the engine now indeed.'],
        long: [2],
        premature: [],
    },
    {
        name: 'a bracket inside a quoted string does not close a brace-delimited type shape',
        lines: ['@phpstan-type T array{', "sep: ')',", 'a: int,', 'b: string,', '}'],
        expected: ['@phpstan-type T array{', "sep: ')',", 'a: int,', 'b: string,', '}'],
        long: [],
        premature: [],
    },
    {
        name: 'a word-bracket in a variable-less tag description does not freeze the next line',
        lines: ['@return bool True if a<b for the current pair of records we are comparing', 'This continuation paragraph is long enough that it really ought to wrap onto a second line here now yes.'],
        expected: ['@return bool True if a<b for the current pair of records we are comparing', 'This continuation paragraph is long enough that it really ought to wrap onto', 'a second line here now yes.'],
        long: [1],
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
        expect(classify('# Heading', false)).toBe(KIND.HEADING);
        expect(classify('###### Deep heading', false)).toBe(KIND.HEADING);
        expect(classify('#nospace', false)).toBe(KIND.PROSE);
        expect(classify('#123 is an issue', false)).toBe(KIND.PROSE);
        expect(classify('anything at all', true)).toBe(KIND.FENCE);
    });

    it('treats keyword-led and span-quoted prose as prose, not code', () => {
        expect(classify('for the test to exercise it now.', false)).toBe(KIND.PROSE);
        expect(classify('if you look here you will see it.', false)).toBe(KIND.PROSE);
        expect(classify('See {@link Foo::bar} for the details.', false)).toBe(KIND.PROSE);
        expect(classify('The `Foo::bar` method is documented.', false)).toBe(KIND.PROSE);
        expect(classify('if ($x) {', false)).toBe(KIND.CODE);
        expect(classify('this.value = 1;', false)).toBe(KIND.CODE);
        expect(classify('Foo::bar();', false)).toBe(KIND.CODE);
    });

    it('recognises a fence delimiter', () => {
        expect(isFence('```')).toBe(true);
        expect(isFence(' text ')).toBe(false);
    });
});

describe('non-prose skips', () => {
    it('detects indentation of a tab or two or more spaces', () => {
        expect(indented('  code here')).toBe(true);
        expect(indented('    deeper')).toBe(true);
        expect(indented('\ttab code')).toBe(true);
        expect(indented(' one space')).toBe(false);
        expect(indented('flush prose')).toBe(false);
    });

    it('measures leading spaces and recognises a type-block boundary', () => {
        expect(indentWidth('  two')).toBe(2);
        expect(indentWidth('      six')).toBe(6);
        expect(indentWidth('flush')).toBe(0);
        expect(isTypeBoundary('')).toBe(true);
        expect(isTypeBoundary('@param int $x')).toBe(true);
        expect(isTypeBoundary('id: int,')).toBe(false);
    });

    it('reports the depth a type-annotation tag opens, guarding stray brackets', () => {
        expect(typeTagDepth('@phpstan-type X array{')).toBe(1);
        expect(typeTagDepth('@param array{a: int, b:')).toBe(1);
        expect(typeTagDepth('@var array<int,')).toBe(1);
        expect(typeTagDepth('@typedef {{')).toBe(2);
        expect(typeTagDepth('@return array<int, string>')).toBe(0);
        expect(typeTagDepth('@return void')).toBe(0);
        expect(typeTagDepth('@param int $x must be < 10 for the loop')).toBe(0);
        expect(typeTagDepth('@param array $opts shaped like {id, name} but documented in prose')).toBe(0);
        expect(typeTagDepth('@psalm-type Row array{')).toBe(1);
        expect(typeTagDepth('@return bool True if a<b for the pair')).toBe(0);
        expect(typeTagDepth('@return int the count(approx')).toBe(0);
        expect(typeTagDepth('a: int,')).toBe(0);
    });

    it('counts net bracket depth over the type slot, ignoring arrows, quotes and description', () => {
        expect(bracketDelta('array{')).toBe(1);
        expect(bracketDelta('}')).toBe(-1);
        expect(bracketDelta('authorize: \\Closure(): void,')).toBe(0);
        expect(bracketDelta('array<int, class-string<X>>,')).toBe(0);
        expect(bracketDelta('id: number => other,')).toBe(0);
        expect(bracketDelta('} $records the records (see the note above)')).toBe(-1);
        expect(bracketDelta("sep: ')',")).toBe(0);
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
