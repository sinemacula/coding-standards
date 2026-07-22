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
     * PrematurelyWrapped, each on its own line, across `//` runs and docblocks,
     * while exempt lines (tags, directives, code, tables, separators, URLs,
     * single-line docblocks and trailing comments) report nothing.
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
        ]);
    }

    /**
     * The fixer reflows every faulted paragraph to its greedy canonical form,
     * preserving indentation, delimiters and hanging list indent, and a second
     * pass over the result leaves it unchanged.
     *
     * @return void
     */
    public function testFixesToGreedyCanonicalForm(): void
    {
        $this->assertFixes('CommentLineLength.inc');
    }

    /**
     * Comments that are already canonical, or exempt from reflow, report no
     * faults at all.
     *
     * @return void
     */
    public function testCanonicalAndExemptCommentsAreClean(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthValid.inc', []);
    }

    /**
     * Adjacent `//` comments at different indents are separate runs: each
     * reflows within its own indent rather than merging into one paragraph.
     *
     * @return void
     */
    public function testKeepsDifferentlyIndentedRunsSeparate(): void
    {
        $this->assertFixes('CommentLineLengthIndent.inc');
    }

    /**
     * Every kind of comment the sniff does not govern is left alone, however
     * far beyond the width it runs: hash comments, single- and multi-line block
     * comments, framework configuration blocks, single-line docblocks, docblock
     * tags, fenced code, tables, separators, trailing comments and malformed
     * docblocks all report no faults.
     *
     * @return void
     */
    public function testEveryUngovernedCommentTypeReportsNoFaults(): void
    {
        $this->assertErrorsOnLines('CommentTypes.inc', []);
    }

    /**
     * Each fault message renders the configured width into its prose, so the
     * report tells the author the exact limit the line must respect.
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
        ]);
    }

    /**
     * A run whose every line overflows reports each line and is still reflowed:
     * the whole run reads as one paragraph and wraps to the greedy form.
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
     * A docblock indented inside a class is governed like one at the margin:
     * its faults are reported and its paragraph reflows within the indent.
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
     * Accented prose measures by characters, not bytes: the indent survives the
     * reflow intact and the wrap point is chosen by visible width.
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
     * A comment line landing exactly on the width limit is canonical: neither
     * the slash form nor the docblock form reports a fault at the boundary.
     *
     * @return void
     */
    public function testLinesAtExactlyTheWidthLimitAreClean(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthBoundary.inc', []);
    }

    /**
     * A docblock shell outside the plain shape is never governed: an opener or
     * closer carrying prose, or an interior line without its leading star,
     * leaves the whole block alone, as does a comment trailing a closing brace.
     *
     * @return void
     */
    public function testShellsOutsideThePlainShapeAreExempt(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthShape.inc', []);
    }

    /**
     * A file with carriage-return line endings measures each line without the
     * carriage return, so a line at exactly the limit stays clean.
     *
     * @return void
     */
    public function testMeasuresCrlfLinesWithoutTheCarriageReturn(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthCrlf.inc', []);
    }

    /**
     * A fault suppressed by an inline annotation is neither reported nor fixed:
     * the fixer must leave a suppressed block byte-for-byte intact.
     *
     * @return void
     */
    public function testSuppressedFaultsAreNeitherReportedNorFixed(): void
    {
        $this->assertErrorsOnLines('CommentLineLengthSuppressed.inc', []);

        $this->assertFixes('CommentLineLengthSuppressed.inc');
    }
}
