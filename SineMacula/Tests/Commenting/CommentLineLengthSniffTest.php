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
     * Over-long standalone comment lines are flagged, while tag lines, lines
     * whose overflow is an unbreakable token, suppression directives
     * (phpstan-ignore), and trailing comments are not.
     *
     * @return void
     */
    public function testFlagsOverLongCommentLines(): void
    {
        $this->assertErrorsOnLines('CommentLineLength.inc', [6, 12]);
    }
}
