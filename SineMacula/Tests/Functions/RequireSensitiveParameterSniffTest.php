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
     * Flags unmarked sensitive parameters, but not lookalikes or test classes.
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
     * Camel-case names match and the message names the parameter.
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
     * Flags only types that can hold the secret, not the object owning it.
     *
     * @return void
     */
    public function testFlagsOnlyTypesThatCanCarryTheSecret(): void
    {
        $this->assertErrorsOnLines('RequireSensitiveParameterTypes.inc', [43, 47, 51, 55, 59, 63, 67, 71, 77]);
    }

    /**
     * The closest enclosing class decides the test exemption.
     *
     * @return void
     */
    public function testExemptsNestedTestClassByClosestEnclosingClass(): void
    {
        $this->assertErrorsOnLines('RequireSensitiveParameterNested.inc', []);
    }
}
