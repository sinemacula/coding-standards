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
     * Sensitive parameters without the attribute are flagged; marked ones, the
     * ambiguous $token, lookalikes such as $tokenizer, and parameters in a test
     * class are not.
     *
     * @return void
     */
    public function testFlagsUnmarkedSensitiveParameters(): void
    {
        $this->assertErrorsOnLines('RequireSensitiveParameter.inc', [7, 11, 15]);
    }

    /**
     * Fixtures under a tests/ directory are exempt whatever their class name.
     *
     * @return void
     */
    public function testExemptsFixturesUnderTestsDirectory(): void
    {
        $this->assertErrorsOnLines('tests/SensitiveParameterFixture.inc', []);
    }
}
