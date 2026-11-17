<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that a method whose body only throws is accepted.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class AlwaysThrowingMethodTest extends AbstractStandardTestCase
{
    /**
     * A method that only throws is accepted when declared `never`.
     *
     * @return void
     */
    public function testAcceptsAnAlwaysThrowingMethodDeclaredNever(): void
    {
        $this->assertStandardReports('AlwaysThrowingMethod.inc', []);
    }

    /**
     * A method that only throws may also keep its declared type.
     *
     * @return void
     */
    public function testAcceptsAnAlwaysThrowingMethodThatKeepsItsDeclaredType(): void
    {
        $this->assertStandardReports('AlwaysThrowingMethodInherited.inc', []);
    }

    /**
     * Only a documented traversable's mixed contained type is reported.
     *
     * @return void
     */
    public function testStillReportsTheMixedContainedTypeAndNothingElse(): void
    {
        $this->assertStandardReports('AlwaysThrowingMethodTyped.inc', [
            23 => ['SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint'],
        ]);
    }
}
