<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that a method existing only to refuse can be written.
 *
 * A guard such as a `__serialize()` that throws, keeping a value holding a
 * secret out of a queue payload or a cache entry, returns nothing on any path.
 * Declaring it `never` says exactly that, and the standard accepts it. Reaching
 * instead for the type the method would have returned had it ever returned puts
 * two rules on the same line, which is what this pins.
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
     * A method whose body only throws is accepted when it is declared `never`,
     * both where an inherited signature nominally asks for another type and
     * where the method is the class's own. `never` is a subtype of every return
     * type, so narrowing to it always satisfies the inherited signature.
     *
     * @return void
     */
    public function testAcceptsAnAlwaysThrowingMethodDeclaredNever(): void
    {
        $this->assertStandardReports('AlwaysThrowingMethod.inc', []);
    }

    /**
     * Declaring the type the method would have returned is what cannot be
     * written. The docblock has to describe a shape the method never produces,
     * which collides with the no-return check and, for a traversable, with the
     * mixed ban as well. Both are reported against the same line.
     *
     * @return void
     */
    public function testRejectsAnAlwaysThrowingMethodDeclaredByItsUnreachableType(): void
    {
        $this->assertStandardReports('AlwaysThrowingMethodTyped.inc', [
            23 => [
                'SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint',
                'Squiz.Commenting.FunctionComment.InvalidNoReturn',
            ],
        ]);
    }

    /**
     * Where the declared type has to stay - a method a subclass is meant to
     * return from, which cannot widen `never` back - the no-return check is
     * suppressed on that one method. The error is raised against the `@return`
     * line, so the directive only takes immediately above that tag: a blank
     * docblock line in between leaves the error standing, and moving it out
     * below the docblock severs the comment from the method it documents.
     *
     * @return void
     */
    public function testAcceptsTheDeclaredTypeWhereTheCheckIsSuppressedOnTheReturnTag(): void
    {
        $this->assertStandardReports('AlwaysThrowingMethodSuppressed.inc', []);
    }
}
