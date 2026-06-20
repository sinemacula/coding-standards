<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the promoted property doc comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
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
