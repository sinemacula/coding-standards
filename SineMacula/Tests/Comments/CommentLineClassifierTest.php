<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Comments;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SineMacula\CodingStandards\Comments\CommentLineClassifier;

/**
 * Tests for the comment line classifier.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(CommentLineClassifier::class)]
final class CommentLineClassifierTest extends TestCase
{
    /**
     * Fence delimiter cases.
     *
     * @return iterable<string, array{string, bool}>
     */
    public static function fenceCases(): iterable
    {
        yield from [
            'backtick fence'          => ['```', true],
            'tilde fence'             => ['~~~', true],
            'fence with a language'   => ['```php', true],
            'indented backtick fence' => ['  ```', true],
            'indented tilde fence'    => ['   ~~~', true],
            'plain prose'             => ['not a fence', false],
            'backticks mid line'      => ['see ``` inline', false],
        ];
    }

    /**
     * A fence delimiter is recognised at any indent, but only at the line
     * start.
     *
     * @param  string  $content
     * @param  bool  $expected
     * @return void
     */
    #[DataProvider('fenceCases')]
    public function testRecognisesFenceDelimiters(string $content, bool $expected): void
    {
        self::assertSame($expected, (new CommentLineClassifier)->isFence($content));
    }

    /**
     * List marker cases.
     *
     * @return iterable<string, array{string, array{marker: string, indent: int, width: int}|null}>
     */
    public static function listMarkerCases(): iterable
    {
        yield from [
            'dash bullet'             => ['- item', ['marker' => '-', 'indent' => 0, 'width' => 2]],
            'star bullet'             => ['* item', ['marker' => '*', 'indent' => 0, 'width' => 2]],
            'plus bullet'             => ['+ item', ['marker' => '+', 'indent' => 0, 'width' => 2]],
            'indented ordinal'        => ['  1. item', ['marker' => '1.', 'indent' => 2, 'width' => 3]],
            'two-digit paren ordinal' => ['10) item', ['marker' => '10)', 'indent' => 0, 'width' => 4]],
            'plain prose'             => ['not a list', null],
            'dash mid sentence'       => ['a phrase with - a dash inside', null],
            'marker without a space'  => ['-item', null],
        ];
    }

    /**
     * A list marker parses only at the line start.
     *
     * @param  string  $content
     * @param  array{marker: string, indent: int, width: int}|null  $expected
     * @return void
     */
    #[DataProvider('listMarkerCases')]
    public function testParsesListMarkersAtTheLineStartOnly(string $content, ?array $expected): void
    {
        self::assertSame($expected, (new CommentLineClassifier)->listMarker($content));
    }

    /**
     * Indent width is measured in code points.
     *
     * @return void
     */
    public function testMeasuresIndentWidthInCodePoints(): void
    {
        $classifier = new CommentLineClassifier;

        self::assertSame(0, $classifier->indentWidth('flush text'));
        self::assertSame(4, $classifier->indentWidth('    indented'));
        self::assertSame(2, $classifier->indentWidth('  héllo wörld'));
    }

    /**
     * Type boundary cases.
     *
     * @return iterable<string, array{string, bool}>
     */
    public static function typeBoundaryCases(): iterable
    {
        yield from [
            'empty line'           => ['', true],
            'whitespace-only line' => ['   ', true],
            'fresh tag'            => ['@param int $x', true],
            'indented tag'         => ['  @return void', true],
            'shape member'         => ['id: int,', false],
            'closing brace'        => ['}', false],
        ];
    }

    /**
     * A blank line or a fresh tag ends a type block.
     *
     * @param  string  $content
     * @param  bool  $expected
     * @return void
     */
    #[DataProvider('typeBoundaryCases')]
    public function testDetectsTypeBoundaries(string $content, bool $expected): void
    {
        self::assertSame($expected, (new CommentLineClassifier)->isTypeBoundary($content));
    }

    /**
     * Type tag depth cases.
     *
     * @return iterable<string, array{string, int}>
     */
    public static function typeTagDepthCases(): iterable
    {
        yield from [
            'shape opening one level'         => ['@phpstan-type Row array{', 1],
            'indented tag still opens'        => [' @phpstan-type Row array{', 1],
            'generic opening over a shape'    => ['@phpstan-type Map array<string, array{', 2],
            'type closed on the same line'    => ['@return array<int, string>', 0],
            'prose with a brace is not a tag' => ['shaped like array{', 0],
            'unrecognised tag opens nothing'  => ['@throws RuntimeException when {', 0],
        ];
    }

    /**
     * A type tag cut off mid-construct opens a block at its net bracket depth.
     *
     * @param  string  $content
     * @param  int  $expected
     * @return void
     */
    #[DataProvider('typeTagDepthCases')]
    public function testMeasuresTypeTagDepth(string $content, int $expected): void
    {
        self::assertSame($expected, (new CommentLineClassifier)->typeTagDepth($content));
    }

    /**
     * Bracket delta cases.
     *
     * @return iterable<string, array{string, int}>
     */
    public static function bracketDeltaCases(): iterable
    {
        yield from [
            'balanced generic'                    => ['array<int, string>', 0],
            'open shape'                          => ['array{', 1],
            'closing line'                        => ['}', -1],
            'arrow operators are not brackets'    => ['factory: fn () => self::make()->build(),', 0],
            'backtick span is ignored'            => ['wraps in `array{` markdown', 0],
            'brace after the variable is ignored' => ['@param array{id: int} $row like {a: 1}', 0],
            'unicode type cut at the variable'    => ['@param array<Größe> $size', 0],
        ];
    }

    /**
     * The bracket delta counts the type slot only.
     *
     * @param  string  $text
     * @param  int  $expected
     * @return void
     */
    #[DataProvider('bracketDeltaCases')]
    public function testComputesBracketDeltaFromTheTypeSlot(string $text, int $expected): void
    {
        self::assertSame($expected, (new CommentLineClassifier)->bracketDelta($text));
    }
}
