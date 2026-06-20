<?php

namespace SineMacula\Tests\Classes;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the final class sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireFinalClassSniffTest extends AbstractSniffTestCase
{
    /**
     * Only concrete classes that are neither final nor marked @inheritable
     * should be flagged.
     *
     * @return void
     */
    public function testFlagsNonFinalConcreteClasses(): void
    {
        $this->assertErrorsOnLines('RequireFinalClass.inc', [13, 33]);
    }
}
