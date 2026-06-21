<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Exceptions\DisallowBaseExceptionSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the base exception throw sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(DisallowBaseExceptionSniff::class)]
final class DisallowBaseExceptionSniffTest extends AbstractSniffTestCase
{
    /**
     * Throwing the base exception is flagged; throwing a domain exception or
     * re-throwing a variable is not.
     *
     * @return void
     */
    public function testFlagsThrowsOfTheBaseException(): void
    {
        $this->assertErrorsOnLines('DisallowBaseException.inc', [11]);
    }
}
