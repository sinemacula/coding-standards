<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\SingleLineMemberCommentSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the single-line data member comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(SingleLineMemberCommentSniff::class)]
final class SingleLineMemberCommentSniffTest extends AbstractSniffTestCase
{
    /**
     * A multi-line doc comment on a property, class constant or enum case is
     * flagged, whatever its modifiers or type; a single-line one, an
     * undocumented member and a documented local variable are not.
     *
     * @return void
     */
    public function testFlagsMultilineDataMemberComments(): void
    {
        $this->assertErrorsOnLines('SingleLineMemberComment.inc', [10, 18, 26, 47]);
    }
}
