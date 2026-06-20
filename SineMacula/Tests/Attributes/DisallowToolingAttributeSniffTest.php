<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Attributes;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the tooling attribute sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class DisallowToolingAttributeSniffTest extends AbstractSniffTestCase
{
    /**
     * Only attributes under a forbidden namespace should be flagged, whether
     * imported, fully qualified, or used with arguments.
     *
     * @return void
     */
    public function testFlagsToolingAttributes(): void
    {
        $this->assertErrorsOnLines('DisallowToolingAttribute.inc', [12, 18, 23]);
    }
}
