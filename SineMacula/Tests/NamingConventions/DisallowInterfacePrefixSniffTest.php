<?php

namespace SineMacula\Tests\NamingConventions;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the interface name prefix sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class DisallowInterfacePrefixSniffTest extends AbstractSniffTestCase
{
    /**
     * Only interfaces using an "I" prefix should be flagged.
     *
     * @return void
     */
    public function testFlagsPrefixedInterfaceNames(): void
    {
        $this->assertErrorsOnLines('DisallowInterfacePrefix.inc', [9]);
    }
}
