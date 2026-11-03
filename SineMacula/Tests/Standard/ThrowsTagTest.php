<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that the @throws tags PHPStan asks for are tags the sniffs accept.
 *
 * PHPStan requires every checked exception a method can throw to be declared,
 * those its callees propagate included, while the throw tag sniff counts only
 * the exceptions thrown in the body itself. A method that both throws and
 * propagates needs more tags than that count, so the count is not held against
 * it; an exception thrown in the body without a tag is still reported.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class ThrowsTagTest extends AbstractStandardTestCase
{
    /**
     * A method that throws one exception and propagates another declares both
     * and is accepted.
     *
     * @return void
     */
    public function testAcceptsATagForAnExceptionACalleePropagates(): void
    {
        $this->assertStandardReports('ThrowsAndPropagates.inc', []);
    }

    /**
     * An exception thrown in the body without a tag is still reported.
     *
     * @return void
     */
    public function testStillReportsAThrownExceptionWithoutATag(): void
    {
        $this->assertStandardReports('ThrowsUndeclared.inc', [
            20 => ['Squiz.Commenting.FunctionCommentThrowTag.Missing'],
        ]);
    }
}
