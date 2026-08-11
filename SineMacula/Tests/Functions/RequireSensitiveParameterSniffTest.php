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
     * A sensitive name only counts where the declared type could carry the
     * secret. A parameter typed solely as a class, interface, `object` or
     * `self`, including nullable, intersection and disjunctive-normal forms,
     * holds what the secret belongs to rather than the secret. A value type
     * still counts wherever it appears - alone, in any case, nullable, or as
     * one member of a union or disjunctive-normal type - as does an untyped
     * parameter, promoted constructor properties included.
     *
     * @return void
     */
    public function testFlagsOnlyTypesThatCanCarryTheSecret(): void
    {
        $this->assertErrorsOnLines('RequireSensitiveParameterTypes.inc', [43, 47, 51, 55, 59, 63, 67, 71, 77]);
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
