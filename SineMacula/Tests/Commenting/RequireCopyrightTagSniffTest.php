<?php

namespace SineMacula\Tests\Commenting;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the copyright tag sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireCopyrightTagSniffTest extends AbstractSniffTestCase
{
    /**
     * A docblock without @copyright is flagged; one with it, and a structure
     * with no docblock at all (left to the class-comment sniff), are not.
     *
     * @return void
     */
    public function testFlagsDocblocksMissingCopyright(): void
    {
        $this->assertErrorsOnLines('RequireCopyrightTag.inc', [20]);
    }
}
