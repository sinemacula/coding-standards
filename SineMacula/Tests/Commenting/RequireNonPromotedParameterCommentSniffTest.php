<?php

namespace SineMacula\Tests\Commenting;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the mixed constructor parameter comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireNonPromotedParameterCommentSniffTest extends AbstractSniffTestCase
{
    /**
     * In a mixed constructor, plain parameters without a preceding comment are
     * flagged; commented ones, promoted properties, and all-promoted
     * constructors are not.
     *
     * @return void
     */
    public function testFlagsUncommentedPlainParametersInMixedConstructors(): void
    {
        $this->assertErrorsOnLines('RequireNonPromotedParameterComment.inc', [11]);
    }
}
