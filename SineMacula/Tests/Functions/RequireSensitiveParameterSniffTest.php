<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Functions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;
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
#[CoversTrait(DetectsTestClasses::class)]
#[CoversTrait(ResolvesQualifiedNames::class)]
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

    /**
     * Camel-case names are matched case-insensitively - $apiKey matches the
     * joined keyword form and $currentPassword matches on a word boundary - the
     * reported message names the offending parameter, and an unqualified
     * imported attribute counts as marked.
     *
     * @return void
     */
    public function testMatchesCamelCaseNamesAndReportsParameterName(): void
    {
        $this->assertErrorMessagesOnLines('RequireSensitiveParameterNaming.inc', [
            9  => ['Parameter "$apiKey" looks sensitive and must be marked #[\SensitiveParameter].'],
            13 => ['Parameter "$currentPassword" looks sensitive and must be marked #[\SensitiveParameter].'],
        ]);
    }

    /**
     * The closest enclosing class decides the test exemption - a test class
     * declared inside a production class method is still exempt.
     *
     * @return void
     */
    public function testExemptsNestedTestClassByClosestEnclosingClass(): void
    {
        $this->assertErrorsOnLines('RequireSensitiveParameterNested.inc', []);
    }
}
