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
     * property in a plain or anonymous class declared inside a method of a
     * readonly, ignored-parent, test-named or ignored-interface class is still
     * flagged.
     *
     * @return void
     */
    public function testDecidesExemptionsByInnermostEnclosingClass(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicPropertyNested.inc', [11, 22, 33, 44, 54, 56]);
    }

    /**
     * The properties listed for an interface are exempt, declared or promoted,
     * in a named or anonymous class whose implements clause names it by short
     * or fully qualified name in any case, alone or among others. A key given
     * fully qualified matches too; names are trimmed and empty entries skipped.
     * Unlisted properties of the same class, a listed name used as a prefix, a
     * property listed only for another interface, an unlisted interface, a
     * subclass inheriting the interface, an interface sharing only a prefix
     * with the listed one and a trait's property are all still flagged.
     *
     * @return void
     */
    public function testExemptsPropertiesListedForAnImplementedInterface(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicPropertyInterface.inc', [15, 17, 21, 40, 45, 50, 55, 60, 71]);
    }

    /**
     * The properties listed for a parent are exempt, declared or promoted, in a
     * named or anonymous class whose extends clause names it by short or fully
     * qualified name in any case, together with those listed for an interface
     * the class implements. Unlisted properties, a grandchild of the listed
     * parent and a parent sharing only a prefix with it are still flagged.
     *
     * @return void
     */
    public function testExemptsPropertiesListedForTheExtendedParent(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicPropertyParent.inc', [13, 17, 38, 43, 48, 54]);
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
     * Exempt classes extending the fixture's mutable entity bases, and the
     * fixture's queued-job and broadcast configuration properties, the latter
     * also through a job base.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return [
            'ignoredParentClasses'       => ['Model', 'Base'],
            'ignoredInterfaceProperties' => [
                'ShouldQueue'                                        => ' tries, timeout ,backoff,,maxExceptions',
                '\Illuminate\Contracts\Broadcasting\ShouldBroadcast' => 'connection',
            ],
            'ignoredParentClassProperties' => [
                'Job' => 'tries, timeout,backoff',
            ],
        ];
    }
}
