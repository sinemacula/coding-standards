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
}
