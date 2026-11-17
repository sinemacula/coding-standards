<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\CommentLineLengthSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the comment line length sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(CommentLineLengthSniff::class)]
final class CommentLineLengthSniffTest extends AbstractSniffTestCase
{
    /**
     * Overflowing prose reports TooLong and prematurely wrapped prose reports
     * PrematurelyWrapped, while exempt lines report nothing.
     *
     * @return void
     */
    public function testReportsOverflowAndPrematureWrappingSeparately(): void
    {
        $this->assertErrorCodesOnLines('CommentLineLength.inc', [
            5  => ['TooLong'],
            7  => ['PrematurelyWrapped'],
            8  => ['PrematurelyWrapped'],
            24 => ['TooLong'],
            26 => ['PrematurelyWrapped'],
            27 => ['PrematurelyWrapped'],
            30 => ['TooLong'],
            47 => ['PrematurelyWrapped'],
            51 => ['PrematurelyWrapped'],
        ]);
    }

    /**
     * The fixer reflows each faulted paragraph to its greedy canonical form.
     *
     * @return void
     */
    public function testFixesToGreedyCanonicalForm(): void
    {
        $this->assertFixes('CommentLineLength.inc');
    }

    /**
     * Canonical and exempt comments report nothing.
     *
     * @return void
     */
    public function testCanonicalAndExemptCommentsAreClean(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthValid.inc', []);
    }

    /**
     * Adjacent `//` runs at different indents reflow separately.
     *
     * @return void
     */
    public function testKeepsDifferentlyIndentedRunsSeparate(): void
    {
        $this->assertFixes('CommentLineLengthIndent.inc');
    }

    /**
     * Comment kinds the sniff does not govern report nothing, however long.
     *
     * @return void
     */
    public function testEveryUngovernedCommentTypeReportsNoFaults(): void
    {
        $this->assertErrorsOnLines('CommentTypes.inc', []);
    }

    /**
     * Fault messages include the configured width.
     *
     * @return void
     */
    public function testRendersTheWidthLimitIntoFaultMessages(): void
    {
        $tooLong   = 'Comment line must not exceed 80 characters.';
        $premature = 'Comment line wraps before it needs to; the next word fits within 80 characters.';

        $this->assertErrorMessagesOnLines('CommentLineLength.inc', [
            5  => [$tooLong],
            7  => [$premature],
            8  => [$premature],
            24 => [$tooLong],
            26 => [$premature],
            27 => [$premature],
            30 => [$tooLong],
            47 => [$premature],
            51 => [$premature],
        ]);
    }

    /**
     * A run whose every line overflows is reported per line and reflowed.
     *
     * @return void
     */
    public function testFixesRunWhereEveryLineOverflows(): void
    {
        $this->assertErrorCodesOnLines('CommentLineLengthOverflowPair.inc', [
            5 => ['TooLong'],
            6 => ['TooLong'],
        ]);

        $this->assertFixes('CommentLineLengthOverflowPair.inc');
    }

    /**
     * An indented docblock is reported and reflowed within its indent.
     *
     * @return void
     */
    public function testReflowsIndentedDocblocks(): void
    {
        $this->assertErrorCodesOnLines('CommentLineLengthIndentedDocblock.inc', [
            8 => ['PrematurelyWrapped'],
        ]);

        $this->assertFixes('CommentLineLengthIndentedDocblock.inc');
    }

    /**
     * Multibyte prose is measured in characters, not bytes.
     *
     * @return void
     */
    public function testReflowsMultibyteProseByCharacterCount(): void
    {
        $this->assertErrorCodesOnLines('CommentLineLengthMultibyte.inc', [
            7 => ['TooLong'],
        ]);

        $this->assertFixes('CommentLineLengthMultibyte.inc');
    }

    /**
     * A line exactly at the width limit is clean.
     *
     * @return void
     */
    public function testLinesAtExactlyTheWidthLimitAreClean(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthBoundary.inc', []);
    }

    /**
     * Docblocks outside the plain shape, and comments after a closing brace,
     * are exempt.
     *
     * @return void
     */
    public function testShellsOutsideThePlainShapeAreExempt(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthShape.inc', []);
    }

    /**
     * CRLF lines are measured without the carriage return.
     *
     * @return void
     */
    public function testMeasuresCrlfLinesWithoutTheCarriageReturn(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthCrlf.inc', []);
    }

    /**
     * A suppressed fault is neither reported nor fixed.
     *
     * @return void
     */
    public function testSuppressedFaultsAreNeitherReportedNorFixed(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthSuppressed.inc', []);

        $this->assertFixes('CommentLineLengthSuppressed.inc');
    }
}
