<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\NamingConventions\ValidEnumCaseNameSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the enum case naming sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(ValidEnumCaseNameSniff::class)]
final class ValidEnumCaseNameSniffTest extends AbstractSniffTestCase
{
    /**
     * Only enum cases that are not SCREAMING_SNAKE_CASE should be flagged.
     *
     * @return void
     */
    public function testFlagsEnumCasesNotInUpperSnakeCase(): void
    {
        $this->assertErrorsOnLines('ValidEnumCaseName.inc', [7, 8, 14]);
    }

    /**
     * The rendered message names the offending enum case, proving the name is
     * passed through to the report.
     *
     * @return void
     */
    public function testReportsTheOffendingCaseNameInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines('ValidEnumCaseName.inc', [
            7  => ['Enum case "Clubs" must be declared in SCREAMING_SNAKE_CASE.'],
            8  => ['Enum case "spades" must be declared in SCREAMING_SNAKE_CASE.'],
            14 => ['Enum case "In_Progress" must be declared in SCREAMING_SNAKE_CASE.'],
        ]);
    }
}
