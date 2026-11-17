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
     * Flags every trait import after the first, in any class-like structure.
     *
     * @return void
     */
    public function testFlagsEveryTraitImportAfterTheFirst(): void
    {
        $this->assertErrorsOnLines('RequireCombinedTraitImport.inc', [21, 30, 36, 41, 59, 65, 71, 85]);
    }

    /**
     * The message names the rule rather than the trait.
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
     * Reports under its own code so a project can exclude this rule alone.
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
