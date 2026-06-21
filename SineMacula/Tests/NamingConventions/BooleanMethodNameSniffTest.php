<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\NamingConventions\BooleanMethodNameSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the boolean method name sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(BooleanMethodNameSniff::class)]
final class BooleanMethodNameSniffTest extends AbstractSniffTestCase
{
    /**
     * Boolean methods whose name is not a predicate are flagged; predicate
     * names, the handle() convention, non-boolean returns, and magic methods
     * are not.
     *
     * @return void
     */
    public function testFlagsNonPredicateBooleanMethods(): void
    {
        $this->assertErrorsOnLines('BooleanMethodName.inc', [22, 27]);
    }
}
