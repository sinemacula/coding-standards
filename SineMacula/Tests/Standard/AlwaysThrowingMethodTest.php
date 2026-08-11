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
 * Declaring it `never` says exactly that. Keeping the type the method would
 * have returned had it ever returned is equally writable, for the method a
 * subclass is meant to return from, which cannot widen `never` back. Whether a
 * documented return is ever produced is a question of control flow, so it is
 * PHPStan's to answer and no sniff here asks it.
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
     * A method a subclass is meant to return from cannot widen `never` back, so
     * it keeps the type it declares and throws anyway. That is accepted as
     * written, with no directive: nothing here counts return statements.
     *
     * @return void
     */
    public function testAcceptsAnAlwaysThrowingMethodThatKeepsItsDeclaredType(): void
    {
        $this->assertStandardReports('AlwaysThrowingMethodInherited.inc', []);
    }

    /**
     * What is still reported is the `mixed` the contained type of a documented
     * traversable drags in, which is the mixed ban doing its own job and has
     * its own directive. It is pinned here so that the one rule left standing
     * on this shape stays visible, and separable from the rest.
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
