<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Functions;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Functions\RequireSensitiveParameterSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the sensitive parameter attribute sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireSensitiveParameterSniff::class)]
final class RequireSensitiveParameterSniffTest extends AbstractSniffTestCase
{
    /**
     * Sensitive parameters without the attribute are flagged; marked ones and
     * lookalikes such as $tokenizer are not.
     *
     * @return void
     */
    public function testFlagsUnmarkedSensitiveParameters(): void
    {
        $this->assertErrorsOnLines('RequireSensitiveParameter.inc', [11]);
    }
}
