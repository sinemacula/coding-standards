<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\RequireConstantCommentSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the class constant doc comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireConstantCommentSniff::class)]
final class RequireConstantCommentSniffTest extends AbstractSniffTestCase
{
    /**
     * Undocumented class constants are flagged; documented and global constants
     * are not. A doc comment is still found when an attribute sits between it
     * and the constant, so an attribute-tagged undocumented constant is flagged
     * while an attribute-tagged documented one is not.
     *
     * @return void
     */
    public function testFlagsUndocumentedClassConstants(): void
    {
        $this->assertErrorsOnLines('RequireConstantComment.inc', [10, 12, 19]);
    }
}
