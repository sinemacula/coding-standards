<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Comments;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SineMacula\CodingStandards\Comments\CommentTokenizer;

/**
 * Tests for the wrapping tokenizer's atomic spans and for the fallbacks that
 * keep malformed prose tokenising predictably when a delimiter never closes.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(CommentTokenizer::class)]
final class CommentTokenizerTest extends TestCase
{
    /**
     * Atomic-span cases: inline code, inline tags and Markdown links hold their
     * spaces, only a brace followed by an at-sign opens a tag span, and
     * multibyte prose splits cleanly.
     *
     * @return iterable<string, array{string, list<string>}>
     */
    public static function atomicSpanCases(): iterable
    {
        yield from [
            'plain words split on spaces'              => ['alpha beta', ['alpha', 'beta']],
            'inline code holds its spaces'             => ['a `b c` d', ['a', '`b c`', 'd']],
            'an empty inline code span closes at once' => ['`` a` b', ['``', 'a`', 'b']],
            'an inline tag holds its spaces'           => ['see {@link Foo bar} now', ['see', '{@link Foo bar}', 'now']],
            'braces without an at-sign bind no span'   => ['{a b}', ['{a', 'b}']],
            'a markdown link holds its spaces'         => ['a [text here](https://x) b', ['a', '[text here](https://x)', 'b']],
            'an empty link label still forms a link'   => ['[](a b) c', ['[](a b)', 'c']],
            'multibyte prose tokenises cleanly'        => ['é `à è` ü', ['é', '`à è`', 'ü']],
        ];
    }

    /**
     * Prose splits on spaces, while inline code, an inline tag and a Markdown
     * link, even one with an empty label, are each consumed as a single token.
     *
     * @param  string  $text
     * @param  list<string>  $expected
     * @return void
     */
    #[DataProvider('atomicSpanCases')]
    public function testKeepsAtomicSpansWhole(string $text, array $expected): void
    {
        self::assertSame($expected, (new CommentTokenizer)->tokenize($text));
    }

    /**
     * Malformed-delimiter cases: every unclosed or misplaced delimiter falls
     * back to an ordinary character, so the surrounding prose still splits on
     * its spaces.
     *
     * @return iterable<string, array{string, list<string>}>
     */
    public static function malformedDelimiterCases(): iterable
    {
        yield from [
            'an unclosed backtick before a space is ordinary'              => ['` a', ['`', 'a']],
            'an unclosed bracket before a space is ordinary'               => ['[ a', ['[', 'a']],
            'an unclosed bracket before a parenthesised group is ordinary' => ['[(a b) c', ['[(a', 'b)', 'c']],
            'a space between label and target breaks the link'             => ['[a] (b) c', ['[a]', '(b)', 'c']],
            'an unclosed target releases the bracket for rescanning'       => ['[`a](b `x y', ['[`a](b `x', 'y']],
            'an unclosed target after a spaced label splits at the space'  => ['[ ](b c', ['[', '](b', 'c']],
        ];
    }

    /**
     * A delimiter that never closes is treated as an ordinary character: the
     * token ends at the next space, a label must be followed at once by its
     * target, and when a target never closes the bracket is rescanned so a span
     * inside it still binds.
     *
     * @param  string  $text
     * @param  list<string>  $expected
     * @return void
     */
    #[DataProvider('malformedDelimiterCases')]
    public function testTokenisesMalformedDelimitersPredictably(string $text, array $expected): void
    {
        self::assertSame($expected, (new CommentTokenizer)->tokenize($text));
    }
}
