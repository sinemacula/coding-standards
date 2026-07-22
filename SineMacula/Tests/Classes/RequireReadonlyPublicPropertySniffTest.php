<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Classes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;
use SineMacula\Sniffs\Classes\RequireReadonlyPublicPropertySniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the readonly public property sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireReadonlyPublicPropertySniff::class)]
#[CoversTrait(DetectsTestClasses::class)]
final class RequireReadonlyPublicPropertySniffTest extends AbstractSniffTestCase
{
    /**
     * Public mutable properties (declared and promoted) are flagged; readonly,
     * non-public and static properties, readonly/ignored-parent classes, and
     * test classes (named *Test or extending *TestCase) are not. Extending a
     * parent outside the ignored list exempts nothing, and a public mutable
     * promoted property is still flagged when non-flaggable parameters precede
     * it.
     *
     * @return void
     */
    public function testFlagsMutablePublicProperties(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicProperty.inc', [7, 18, 59, 63]);
    }

    /**
     * The rendered error names the offending property so the report points at
     * the declaration that must change.
     *
     * @return void
     */
    public function testReportsTheOffendingPropertyName(): void
    {
        $this->assertErrorMessagesOnLines('RequireReadonlyPublicProperty.inc', [
            7  => ['Public property "$mutable" must be readonly; mutable public state breaks encapsulation.'],
            18 => ['Public property "$promotedMutable" must be readonly; mutable public state breaks encapsulation.'],
            59 => ['Public property "$width" must be readonly; mutable public state breaks encapsulation.'],
            63 => ['Public property "$second" must be readonly; mutable public state breaks encapsulation.'],
        ]);
    }

    /**
     * Exemption is decided by the innermost enclosing class: a mutable public
     * property in a plain class declared inside a method of a readonly,
     * ignored-parent or test-named class is still flagged.
     *
     * @return void
     */
    public function testDecidesExemptionsByInnermostEnclosingClass(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicPropertyNested.inc', [11, 22, 33]);
    }

    /**
     * Test doubles under a tests/ directory are exempt whatever their name.
     *
     * @return void
     */
    public function testExemptsTestDoublesUnderTestsDirectory(): void
    {
        $this->assertErrorsOnLines('tests/ReadonlyTestDouble.inc', []);
    }

    /**
     * Exempt classes extending the fixture's mutable entity bases.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return ['ignoredParentClasses' => ['Model', 'Base']];
    }
}
