<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Classes;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Classes\RequireCombinedTraitImportSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the combined trait import sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireCombinedTraitImportSniff::class)]
final class RequireCombinedTraitImportSniffTest extends AbstractSniffTestCase
{
    /** The single report this sniff makes. */
    private const string MESSAGE = 'Traits must be imported in a single use statement.';

    /**
     * Every import after the first is flagged, in a class, a trait, an enum and
     * an anonymous class alike. Separating the statements with other members
     * does not excuse them, a single import is accepted, and the combined form
     * is what the rule asks for, so neither is reported.
     *
     * @return void
     */
    public function testFlagsEveryTraitImportAfterTheFirst(): void
    {
        $this->assertErrorsOnLines('RequireCombinedTraitImport.inc', [21, 30, 36, 41, 59, 65, 71, 85]);
    }

    /**
     * The report names the rule rather than the trait, since the fix is to
     * merge the statements rather than to change any one import.
     *
     * @return void
     */
    public function testReportsTheCombinedImportRule(): void
    {
        $this->assertErrorMessagesOnLines('RequireCombinedTraitImport.inc', [
            21 => [self::MESSAGE],
            30 => [self::MESSAGE],
            36 => [self::MESSAGE],
            41 => [self::MESSAGE],
            59 => [self::MESSAGE],
            65 => [self::MESSAGE],
            71 => [self::MESSAGE],
            85 => [self::MESSAGE],

        ]);
    }

    /**
     * The report carries its own code, which is what a project excludes to
     * silence this rule alone rather than the sniff it sits beside.
     *
     * @return void
     */
    public function testReportsUnderItsOwnCode(): void
    {
        $this->assertErrorCodesOnLines('RequireCombinedTraitImport.inc', [
            21 => ['NotCombined'],
            30 => ['NotCombined'],
            36 => ['NotCombined'],
            41 => ['NotCombined'],
            59 => ['NotCombined'],
            65 => ['NotCombined'],
            71 => ['NotCombined'],
            85 => ['NotCombined'],

        ]);
    }
}
