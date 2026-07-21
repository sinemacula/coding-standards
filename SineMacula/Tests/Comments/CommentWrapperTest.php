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
                ['$x = compute($this->value);'],
                3,
                ['$x = compute($this->value);'],
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
}
