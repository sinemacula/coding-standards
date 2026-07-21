<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Comments;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SineMacula\CodingStandards\Comments\CommentLineClassifier;
use SineMacula\CodingStandards\Comments\CommentParagraph;
use SineMacula\CodingStandards\Comments\CommentTokenizer;
use SineMacula\CodingStandards\Comments\CommentWrapper;

/**
 * Tests for the deterministic comment reflow engine and its collaborators.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(CommentWrapper::class)]
#[CoversClass(CommentParagraph::class)]
#[CoversClass(CommentTokenizer::class)]
#[CoversClass(CommentLineClassifier::class)]
final class CommentWrapperTest extends TestCase
{
    /** @var string A representative line of commented-out code, left verbatim. */
    private const string CODE_LINE = '$x = compute($this->value);';

    /** @var string A tag whose description holds a bracket, but which opens no type. */
    private const string STRAY_BRACKET_TAG = '@return bool True if a<b for the pair';

    /**
     * Cases exercising overflow, premature wrapping, atoms, lists and the
     * verbatim kinds, each with its expected canonical lines and fault indices.
     *
     * @return iterable<string, array{list<string>, int, list<string>, list<int>, list<int>}>
     */
    public static function reflowCases(): iterable
    {
        yield from [
            'overflowing prose wraps' => [
                ['This is a long descriptive prose paragraph inside the docblock that runs comfortably beyond eighty.'],
                3,
                [
                    'This is a long descriptive prose paragraph inside the docblock that runs',
                    'comfortably beyond eighty.',
                ],
                [0],
                [],
            ],
            'prematurely wrapped prose merges' => [
                [
                    'This paragraph was wrapped',
                    'far too early on every',
                    'single line here.',
                ],
                3,
                ['This paragraph was wrapped far too early on every single line here.'],
                [],
                [0, 1],
            ],
            'canonical prose is untouched' => [
                [
                    'Keeps standalone comment lines within a readable width. Docblock tag lines',
                    '(@param, @return, ...) are exempt, as is any line whose overflow is a single',
                    'unbreakable token.',
                ],
                3,
                [
                    'Keeps standalone comment lines within a readable width. Docblock tag lines',
                    '(@param, @return, ...) are exempt, as is any line whose overflow is a single',
                    'unbreakable token.',
                ],
                [],
                [],
            ],
            'overflow and premature together' => [
                [
                    'Tiny.',
                    'This second line of the same paragraph is intentionally written to overflow far past the eighty limit here.',
                ],
                3,
                [
                    'Tiny. This second line of the same paragraph is intentionally written to',
                    'overflow far past the eighty limit here.',
                ],
                [1],
                [0],
            ],
            'list item keeps hanging indent' => [
                [
                    '- first item that is quite long and will need to wrap onto a second line with a hanging indent applied',
                    '- second short item',
                ],
                3,
                [
                    '- first item that is quite long and will need to wrap onto a second line with',
                    '  a hanging indent applied',
                    '- second short item',
                ],
                [0],
                [],
            ],
            'ordered list item wraps' => [
                ['1. an ordered item that also runs long enough that it must wrap onto another line under the number'],
                3,
                [
                    '1. an ordered item that also runs long enough that it must wrap onto another',
                    '   line under the number',
                ],
                [0],
                [],
            ],
            'inline code stays whole' => [
                ['The value `some spaced code` must never split across the wrap boundary even when it is long here.'],
                3,
                [
                    'The value `some spaced code` must never split across the wrap boundary even',
                    'when it is long here.',
                ],
                [0],
                [],
            ],
            'bare tag never opens a line' => [
                ['Prose that mentions tags such as @param and @return which could otherwise start lines here ok yes now.'],
                3,
                [
                    'Prose that mentions tags such as @param and @return which could otherwise',
                    'start lines here ok yes now.',
                ],
                [0],
                [],
            ],
            'url line is left unwrapped' => [
                ['See https://example.com/a/very/long/path/that/keeps/going/well/past/the/eighty/character/limit/ok'],
                3,
                ['See https://example.com/a/very/long/path/that/keeps/going/well/past/the/eighty/character/limit/ok'],
                [],
                [],
            ],
            'commented-out code is verbatim' => [
                [self::CODE_LINE],
                3,
                [self::CODE_LINE],
                [],
                [],
            ],
            'directive is verbatim' => [
                ['phpcs:ignore Foo.Bar.Baz because the position of this directive line is fixed and cannot move.'],
                3,
                ['phpcs:ignore Foo.Bar.Baz because the position of this directive line is fixed and cannot move.'],
                [],
                [],
            ],
            'a mid-prose tag glued to the previous word is already canonical' => [
                ['alpha bravo charlie delta echo foxtrot golf hotel india juliet then', 'check @internal and keep going with lots more trailing words to spill'],
                3,
                ['alpha bravo charlie delta echo foxtrot golf hotel india juliet then', 'check @internal and keep going with lots more trailing words to spill'],
                [],
                [],
            ],
            'a paragraph that would re-classify once wrapped is left verbatim' => [
                ['alpha bravo charlie delta echo foxtrot golf hotel india juliet kilo lima mike |foo and more words'],
                3,
                ['alpha bravo charlie delta echo foxtrot golf hotel india juliet kilo lima mike |foo and more words'],
                [],
                [],
            ],
            'prose wrapping with a keyword at the line start still reflows' => [
                ['A description paragraph that is long enough to wrap onto a second line here for the test to exercise it now.'],
                3,
                ['A description paragraph that is long enough to wrap onto a second line here', 'for the test to exercise it now.'],
                [0],
                [],
            ],
        ];
    }

    /**
     * Reflowing a paragraph fills each line greedily, reports overflowing and
     * prematurely wrapped lines on their own indices, and leaves atoms, lists,
     * canonical prose and verbatim kinds intact. Wrapping the result again is a
     * stable no-op, so the fix converges in a single pass.
     *
     * @param  list<string>  $lines
     * @param  int  $margin
     * @param  list<string>  $expected
     * @param  list<int>  $long
     * @param  list<int>  $premature
     * @return void
     */
    #[DataProvider('reflowCases')]
    public function testReflowsToGreedyCanonicalForm(array $lines, int $margin, array $expected, array $long, array $premature): void
    {
        $wrapper = new CommentWrapper;
        $result  = $wrapper->wrap($lines, $margin);

        self::assertSame($expected, $result['lines']);
        self::assertSame($long, $result['long']);
        self::assertSame($premature, $result['premature']);

        $again = $wrapper->wrap($result['lines'], $margin);

        self::assertSame($expected, $again['lines']);
        self::assertSame([], $again['long']);
        self::assertSame([], $again['premature']);
    }

    /**
     * The width the reflow fills to follows the configured maximum length.
     *
     * @return void
     */
    public function testHonoursConfiguredMaxLength(): void
    {
        $result = (new CommentWrapper(40))->wrap(['alpha beta gamma delta epsilon zeta eta theta iota kappa'], 3);

        self::assertSame(['alpha beta gamma delta epsilon zeta', 'eta theta iota kappa'], $result['lines']);
        self::assertSame([0], $result['long']);
    }

    /**
     * Prose on either side of a fenced block is reflowed while the fence and
     * its contents stay verbatim, and the result is stable.
     *
     * @return void
     */
    public function testReflowsProseAroundAFencedBlock(): void
    {
        $lines = [
            'Some intro prose that is long enough to wrap onto a second line here for the test yes.',
            '',
            '```',
            'code inside the fence that is very long and must never be reflowed at all no matter how long',
            '```',
            'Trailing prose after the fence that is also long enough to wrap onto a second line here now.',
        ];

        $result = (new CommentWrapper)->wrap($lines, 3);

        self::assertSame([
            'Some intro prose that is long enough to wrap onto a second line here for the',
            'test yes.',
            '',
            '```',
            'code inside the fence that is very long and must never be reflowed at all no matter how long',
            '```',
            'Trailing prose after the fence that is also long enough to wrap onto a second',
            'line here now.',
        ], $result['lines']);
        self::assertSame([0, 5], $result['long']);
        self::assertSame($result['lines'], (new CommentWrapper)->wrap($result['lines'], 3)['lines']);
    }

    /**
     * The tokenizer keeps inline code, link tags and Markdown links whole, and
     * treats an unclosed delimiter as an ordinary character.
     *
     * @return void
     */
    public function testTokenizerKeepsAtomicSpansWhole(): void
    {
        $tokenizer = new CommentTokenizer;

        self::assertSame(['a', '`b c`', 'd'], $tokenizer->tokenize('a `b c` d'));
        self::assertSame(['see', '{@link Foo bar}', 'now'], $tokenizer->tokenize('see {@link Foo bar} now'));
        self::assertSame(['a', '[text here](https://x)', 'b'], $tokenizer->tokenize('a [text here](https://x) b'));
        self::assertSame(['a', '`b', 'c'], $tokenizer->tokenize('a `b c'));
        self::assertSame(['a', '[b]', 'c'], $tokenizer->tokenize('a [b] c'));
    }

    /**
     * The classifier names each kind of comment line.
     *
     * @return void
     */
    public function testClassifierRecognisesEachLineKind(): void
    {
        $classifier = new CommentLineClassifier;

        self::assertSame(CommentLineClassifier::BLANK, $classifier->classify('   ', false));
        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('A sentence.', false));
        self::assertSame(CommentLineClassifier::TAG, $classifier->classify('@param int $x', false));
        self::assertSame(CommentLineClassifier::DIRECTIVE, $classifier->classify('phpcs:ignore Foo.Bar', false));
        self::assertSame(CommentLineClassifier::TABLE, $classifier->classify('| a | b |', false));
        self::assertSame(CommentLineClassifier::SEPARATOR, $classifier->classify('======', false));
        self::assertSame(CommentLineClassifier::LIST, $classifier->classify('- item', false));
        self::assertSame(CommentLineClassifier::LIST, $classifier->classify('1. item', false));
        self::assertSame(CommentLineClassifier::CODE, $classifier->classify('return $x;', false));
        self::assertSame(CommentLineClassifier::FENCE, $classifier->classify('```php', false));
        self::assertSame(CommentLineClassifier::FENCE, $classifier->classify('anything at all', true));

        self::assertTrue($classifier->isFence('```'));
        self::assertFalse($classifier->isFence('not a fence'));
    }

    /**
     * A tool-directive comment is recognised whatever the tool, while prose
     * that merely begins with a similar word is left as prose.
     *
     * @return void
     */
    public function testClassifierRecognisesToolDirectives(): void
    {
        $classifier = new CommentLineClassifier;

        self::assertSame(CommentLineClassifier::DIRECTIVE, $classifier->classify('eslint no-console: "error"', false));
        self::assertSame(CommentLineClassifier::DIRECTIVE, $classifier->classify('biome-ignore lint/style/x: ok', false));
        self::assertSame(CommentLineClassifier::DIRECTIVE, $classifier->classify('Stryker disable all: metadata', false));
        self::assertSame(CommentLineClassifier::DIRECTIVE, $classifier->classify('c8 ignore next -- skip', false));
        self::assertSame(CommentLineClassifier::DIRECTIVE, $classifier->classify('istanbul ignore else -- legacy', false));
        self::assertSame(CommentLineClassifier::DIRECTIVE, $classifier->classify('global fetch, Headers', false));
        self::assertSame(CommentLineClassifier::DIRECTIVE, $classifier->classify('exported handler', false));

        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('globalization is being added', false));
        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('exports are defined below here', false));
        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('note this is ordinary prose here', false));
    }

    /**
     * Prose that merely starts with a keyword, or mentions code inside an
     * inline span such as a `{@link}` or backticks, is prose; a line carrying
     * real code syntax is code.
     *
     * @return void
     */
    public function testDistinguishesProseFromCode(): void
    {
        $classifier = new CommentLineClassifier;

        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('for the test to exercise it now.', false));
        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('if you look here you will see it.', false));
        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('See {@link Foo::bar} for the details.', false));
        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('The `$this->value` shorthand is neat.', false));

        self::assertSame(CommentLineClassifier::CODE, $classifier->classify(self::CODE_LINE, false));
        self::assertSame(CommentLineClassifier::CODE, $classifier->classify('if ($x) {', false));
        self::assertSame(CommentLineClassifier::CODE, $classifier->classify('Foo::bar();', false));
        self::assertSame(CommentLineClassifier::CODE, $classifier->classify('}', false));
    }

    /**
     * A markdown heading, an indented code or command block, and a
     * type-annotation tag whose bracketed value spans lines are each left
     * verbatim rather than reflowed as prose.
     *
     * @return void
     */
    public function testLeavesNonProseConstructsVerbatim(): void
    {
        $this->assertVerbatim(['### Relation existence', 'Bare array form is the short one here now.']);
        $this->assertVerbatim([
            'Run these commands in order:',
            '',
            '  node gen.mjs > runtime-env.json',
            '  aws s3 cp runtime-env.json "s3://a-very-long-bucket-name/path/env.json" --cache-control no-store',
        ]);
        $this->assertVerbatim([
            '@phpstan-type ServiceHooks array{',
            '    authorize: \Closure(): void,',
            '    concerns: array<int, class-string<X>>,',
            '}',
        ]);
        $this->assertVerbatim(['@typedef {{', 'id: number,', 'name: string,', '}} Record']);
        $this->assertVerbatim(['@psalm-type UserRow array{', 'id: int,', 'name: string,', '}']);
        $this->assertVerbatim(['@phpstan-type T array{', 'sep: \')\',', 'a: int,', 'b: string,', '}']);
        $this->assertVerbatim([
            '- Build the container image for production before you deploy it',
            '      docker build -t app:prod . \\',
            '          --no-cache',
        ]);
        $this->assertVerbatim([
            'Run these commands:',
            '',
            "\tnpm install && npm run build with a very long tail that exceeds the eighty limit yes",
        ]);
    }

    /**
     * A stray bracket in a tag's description never opens a verbatim type block
     * that swallows the rest of the comment: a following prose paragraph is
     * still reflowed and its overflow flagged.
     *
     * @return void
     */
    public function testAStrayBracketInADescriptionDoesNotFreezeFollowingProse(): void
    {
        $result = (new CommentWrapper)->wrap([
            self::STRAY_BRACKET_TAG,
            '',
            'This trailing paragraph is genuinely long enough that it should be reflowed by the engine now indeed.',
        ], 3);

        self::assertSame([
            self::STRAY_BRACKET_TAG,
            '',
            'This trailing paragraph is genuinely long enough that it should be reflowed',
            'by the engine now indeed.',
        ], $result['lines']);
        self::assertSame([2], $result['long']);
    }

    /**
     * The classifier recognises a markdown heading, a line indented past the
     * prose baseline, and the depth a type-annotation tag opens, guarding a
     * stray bracket in a prose description.
     *
     * @return void
     */
    public function testClassifierDetectsNonProseConstructs(): void
    {
        $classifier = new CommentLineClassifier;

        self::assertSame(CommentLineClassifier::HEADING, $classifier->classify('# Heading', false));
        self::assertSame(CommentLineClassifier::HEADING, $classifier->classify('###### Deep', false));
        self::assertSame(CommentLineClassifier::PROSE, $classifier->classify('#nospace', false));

        self::assertTrue($classifier->indented('  code here'));
        self::assertTrue($classifier->indented("\ttab code"));
        self::assertFalse($classifier->indented(' one space'));

        self::assertSame(2, $classifier->indentWidth('  two'));
        self::assertSame(0, $classifier->indentWidth('flush'));
        self::assertTrue($classifier->isTypeBoundary(''));
        self::assertTrue($classifier->isTypeBoundary('@param int $x'));
        self::assertFalse($classifier->isTypeBoundary('id: int,'));

        self::assertSame(1, $classifier->typeTagDepth('@phpstan-type X array{'));
        self::assertSame(1, $classifier->typeTagDepth('@psalm-type Row array{'));
        self::assertSame(2, $classifier->typeTagDepth('@typedef {{'));
        self::assertSame(0, $classifier->typeTagDepth('@return array<int, string>'));
        self::assertSame(0, $classifier->typeTagDepth('@param int $x must be < 10 here'));
        self::assertSame(0, $classifier->typeTagDepth('@param array $o shaped like {id, name} in prose'));
        self::assertSame(0, $classifier->typeTagDepth(self::STRAY_BRACKET_TAG));
        self::assertSame(0, $classifier->typeTagDepth('@return int the count(approx'));
        self::assertSame(0, $classifier->bracketDelta('authorize: \Closure(): void,'));
        self::assertSame(-1, $classifier->bracketDelta('} $records the records (see note)'));
        self::assertSame(0, $classifier->bracketDelta('sep: \')\','));
    }

    /**
     * The classifier parses a list marker's bullet, indent and occupied width,
     * and yields nothing for a non-list line.
     *
     * @return void
     */
    public function testClassifierParsesListMarkers(): void
    {
        $classifier = new CommentLineClassifier;

        self::assertSame(['marker' => '-', 'indent' => 0, 'width' => 2], $classifier->listMarker('- item'));
        self::assertSame(['marker' => '1.', 'indent' => 2, 'width' => 3], $classifier->listMarker('  1. item'));
        self::assertNull($classifier->listMarker('not a list'));
    }

    /**
     * Widths count Unicode code points, so an astral emoji is one column and a
     * line of 76 columns fits within 80 rather than overflowing.
     *
     * @return void
     */
    public function testCountsCodePointsNotBytes(): void
    {
        $line   = 'aaaa ' . str_repeat("\u{1F600}", 3) . ' bbbb cccc dddd eeee ffff gggg hhhh iiii jjjj kkkk llll mmmm nnnn oo';
        $result = (new CommentWrapper)->wrap([$line], 3);

        self::assertSame([$line], $result['lines']);
        self::assertSame([], $result['long']);
    }

    /**
     * Whitespace is matched as ASCII only, so a line of a single non-breaking
     * space is prose, not a blank boundary, and the paragraph merges across it.
     *
     * @return void
     */
    public function testTreatsUnicodeWhitespaceAsProse(): void
    {
        $result = (new CommentWrapper)->wrap(['prose line one that is fairly short and mergeable', "\u{00a0}", 'prose line two also short here'], 3);

        self::assertSame(CommentLineClassifier::PROSE, (new CommentLineClassifier)->classify("\u{00a0}", false));
        self::assertSame([0, 1], $result['premature']);
        self::assertCount(2, $result['lines']);
    }

    /**
     * Assert that a block is returned exactly as given, with no faults.
     *
     * @param  list<string>  $lines
     * @return void
     */
    private function assertVerbatim(array $lines): void
    {
        $result = (new CommentWrapper)->wrap($lines, 3);

        self::assertSame($lines, $result['lines']);
        self::assertSame([], $result['long']);
        self::assertSame([], $result['premature']);
    }
}
