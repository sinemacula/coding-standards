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
     * Flags mutable public properties, declared or promoted, unless exempt.
     *
     * @return void
     */
    public function testFlagsMutablePublicProperties(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicProperty.inc', [7, 18, 59, 63, 75]);
    }

    /**
     * The message names the offending property.
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
            75 => ['Public property "$count" must be readonly; mutable public state breaks encapsulation.'],
        ]);
    }

    /**
     * Exemptions are decided by the innermost enclosing class.
     *
     * @return void
     */
    public function testDecidesExemptionsByInnermostEnclosingClass(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicPropertyNested.inc', [11, 22, 33, 44, 54, 56]);
    }

    /**
     * Exempts the properties listed for an interface the class implements.
     *
     * @return void
     */
    public function testExemptsPropertiesListedForAnImplementedInterface(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicPropertyInterface.inc', [15, 17, 21, 40, 45, 50, 55, 60, 71]);
    }

    /**
     * Exempts only the properties listed for the class's direct parent.
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
     * Configure the fixture's ignored parents and listed properties.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return [
            'ignoredParentClasses'       => ['\Illuminate\Database\Eloquent\Model', 'base'],
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
