<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\RequirePromotedPropertyCommentSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the promoted property doc comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequirePromotedPropertyCommentSniff::class)]
final class RequirePromotedPropertyCommentSniffTest extends AbstractSniffTestCase
{
    /**
     * Only promoted properties without a doc comment should be flagged;
     * documented and non-promoted parameters should not.
     *
     * @return void
     */
    public function testFlagsUndocumentedPromotedProperties(): void
    {
        $this->assertErrorsOnLines('RequirePromotedPropertyComment.inc', [10]);
    }
}
