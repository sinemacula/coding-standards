<?php

namespace SineMacula\Tests\NamingConventions;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the boolean method name sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class BooleanMethodNameSniffTest extends AbstractSniffTestCase
{
    /**
     * Boolean methods whose name is not a predicate are flagged; predicate
     * names, non-boolean returns, and magic methods are not.
     *
     * @return void
     */
    public function testFlagsNonPredicateBooleanMethods(): void
    {
        $this->assertErrorsOnLines('BooleanMethodName.inc', [22, 27]);
    }
}
