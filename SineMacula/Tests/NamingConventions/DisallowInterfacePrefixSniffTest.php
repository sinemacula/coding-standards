<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the interface name prefix sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
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
