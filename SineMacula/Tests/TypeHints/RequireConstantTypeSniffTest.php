<?php

declare(strict_types = 1);

namespace SineMacula\Tests\TypeHints;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\TypeHints\RequireConstantTypeSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the typed class constant sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireConstantTypeSniff::class)]
final class RequireConstantTypeSniffTest extends AbstractSniffTestCase
{
    /**
     * Only untyped class constants should be flagged; typed and global
     * constants should not.
     *
     * @return void
     */
    public function testFlagsUntypedClassConstants(): void
    {
        $this->assertErrorsOnLines('RequireConstantType.inc', [7, 13]);
    }

    /**
     * Tight spacing around the equals sign is still flagged; a comment between
     * `const` and the name is not.
     *
     * @return void
     */
    public function testHandlesTightSpacingAroundEquals(): void
    {
        $this->assertErrorsOnLines('RequireConstantTypeTight.inc', [7, 9]);
    }

    /**
     * The rendered error message names the offending constant.
     *
     * @return void
     */
    public function testErrorMessageNamesTheConstant(): void
    {
        $this->assertErrorMessagesOnLines('RequireConstantType.inc', [
            7  => ['Class constant "UNTYPED" must declare a type.'],
            13 => ['Class constant "ANOTHER" must declare a type.'],
        ]);
    }
}
