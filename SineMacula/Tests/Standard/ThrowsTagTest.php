<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that @throws tags for propagated exceptions are accepted.
 *
 * The sniff sees only the body's throws, so extra tags are allowed.
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
            20 => ['SineMacula.Commenting.RequireThrowsTag.Missing'],
        ]);
    }

    /**
     * An exception thrown in the body is reported when other tags are present
     * but none declares it.
     *
     * @return void
     */
    public function testReportsAThrownExceptionAmongOtherTags(): void
    {
        $this->assertStandardReports('ThrowsPartlyDeclared.inc', [
            22 => ['SineMacula.Commenting.RequireThrowsTag.Missing'],
        ]);
    }
}
