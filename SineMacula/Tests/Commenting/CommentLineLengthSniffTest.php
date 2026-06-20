<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the comment line length sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class CommentLineLengthSniffTest extends AbstractSniffTestCase
{
    /**
     * Over-long standalone comment lines are flagged, while tag lines, lines
     * whose overflow is an unbreakable token, and trailing comments are not.
     *
     * @return void
     */
    public function testFlagsOverLongCommentLines(): void
    {
        $this->assertErrorsOnLines('CommentLineLength.inc', [6, 12]);
    }
}
