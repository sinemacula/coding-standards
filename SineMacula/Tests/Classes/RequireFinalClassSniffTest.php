<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Classes;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Classes\RequireFinalClassSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the final class sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireFinalClassSniff::class)]
final class RequireFinalClassSniffTest extends AbstractSniffTestCase
{
    /**
     * Only concrete classes that are neither final nor marked @inheritable
     * should be flagged. The @inheritable opt-out still holds when an attribute
     * sits between the docblock and the class; a concrete class behind an
     * attribute with no opt-out is still flagged.
     *
     * @return void
     */
    public function testFlagsNonFinalConcreteClasses(): void
    {
        $this->assertErrorsOnLines('RequireFinalClass.inc', [13, 33, 48]);
    }
}
