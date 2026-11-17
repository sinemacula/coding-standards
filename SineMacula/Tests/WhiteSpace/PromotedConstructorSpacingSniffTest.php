<?php

declare(strict_types = 1);

namespace SineMacula\Tests\WhiteSpace;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\WhiteSpace\PromotedConstructorSpacingSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the promoted constructor spacing sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(PromotedConstructorSpacingSniff::class)]
final class PromotedConstructorSpacingSniffTest extends AbstractSniffTestCase
{
    /**
     * A promoted constructor pads each parameter with a blank line above it;
     * single-line and non-promoted constructors are left alone.
     *
     * @return void
     */
    public function testFlagsUnpaddedPromotedConstructors(): void
    {
        $this->assertErrorsOnLines('PromotedConstructorSpacing.inc', [21, 23]);
    }

    /**
     * Only a directive comment trailing the opening parenthesis is skipped.
     *
     * @return void
     */
    public function testHandlesTrailingComments(): void
    {
        $this->assertErrorsOnLines('PromotedConstructorSpacingComments.inc', [16, 25, 35]);
    }

    /**
     * The rendered error message names the offending parameter.
     *
     * @return void
     */
    public function testErrorMessageNamesTheParameter(): void
    {
        $this->assertErrorMessagesOnLines('PromotedConstructorSpacing.inc', [
            21 => ['Constructor parameter "$id" must be preceded by a blank line.'],
            23 => ['Constructor parameter "$name" must be preceded by a blank line.'],
        ]);
    }
}
