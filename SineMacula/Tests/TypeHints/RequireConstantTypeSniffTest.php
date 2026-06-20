<?php

namespace SineMacula\Tests\TypeHints;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the typed class constant sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireConstantTypeSniffTest extends AbstractSniffTestCase
{
    /**
     * Only untyped class constants should be flagged; typed and global
     * constants should not.
     *
     * @return void
     */
    public function testFlagsUntypedClassConstants(): void
    {
        $this->assertErrorsOnLines('RequireConstantType.inc', [7, 13]);
    }
}
