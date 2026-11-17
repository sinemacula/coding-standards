<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\RequireNonPromotedParameterCommentSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the mixed constructor parameter comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireNonPromotedParameterCommentSniff::class)]
final class RequireNonPromotedParameterCommentSniffTest extends AbstractSniffTestCase
{
    /**
     * In a mixed constructor, plain parameters without a preceding comment are
     * flagged.
     *
     * @return void
     */
    public function testFlagsUncommentedPlainParametersInMixedConstructors(): void
    {
        $this->assertErrorsOnLines('RequireNonPromotedParameterComment.inc', [11]);
    }

    /**
     * A comment documents only the parameter after it, and plain-only
     * constructors are exempt.
     *
     * @return void
     */
    public function testHandlesEdgeCommentPlacementAndPlainOnlyConstructors(): void
    {
        $this->assertErrorsOnLines('RequireNonPromotedParameterCommentEdges.inc', [27]);
    }

    /**
     * The reported message names the offending parameter.
     *
     * @return void
     */
    public function testReportsParameterNameInMessage(): void
    {
        $this->assertErrorMessagesOnLines('RequireNonPromotedParameterComment.inc', [
            11 => ['Non-promoted parameter "$name" mixed with promoted properties must carry a preceding comment.'],
        ]);
    }
}
