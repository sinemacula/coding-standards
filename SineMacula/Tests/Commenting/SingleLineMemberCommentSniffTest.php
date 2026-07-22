<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
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
#[CoversTrait(ResolvesDocComment::class)]
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

    /**
     * Interface constants are governed too: a multi-line doc comment on an
     * interface constant is flagged and a single-line one is not.
     *
     * @return void
     */
    public function testFlagsMultilineInterfaceConstantComments(): void
    {
        $this->assertErrorsOnLines('SingleLineMemberCommentInterface.inc', [10]);
    }
}
