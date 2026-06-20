<?php

namespace SineMacula\Tests\Commenting;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the class constant doc comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireConstantCommentSniffTest extends AbstractSniffTestCase
{
    /**
     * Undocumented class constants are flagged; documented and global constants
     * are not.
     *
     * @return void
     */
    public function testFlagsUndocumentedClassConstants(): void
    {
        $this->assertErrorsOnLines('RequireConstantComment.inc', [10, 12]);
    }
}
