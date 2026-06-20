<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the enum case naming sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
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
}
