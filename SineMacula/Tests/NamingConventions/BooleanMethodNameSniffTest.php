<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
use SineMacula\Sniffs\NamingConventions\BooleanMethodNameSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the boolean method name sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(BooleanMethodNameSniff::class)]
#[CoversTrait(ResolvesDocComment::class)]
final class BooleanMethodNameSniffTest extends AbstractSniffTestCase
{
    /**
     * Flags boolean methods whose names read as neither predicates nor
     * commands, unless tagged @imperative.
     *
     * @return void
     */
    public function testFlagsNonPredicateBooleanMethods(): void
    {
        $this->assertErrorsOnLines('BooleanMethodName.inc', [87, 134, 139, 144, 149, 159]);
    }

    /**
     * The message names the offending method.
     *
     * @return void
     */
    public function testReportsTheOffendingMethodNameInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines('BooleanMethodName.inc', [
            87  => ['Boolean method "ready" should read as a predicate (is/has/can/...).'],
            134 => ['Boolean method "available" should read as a predicate (is/has/can/...).'],
            139 => ['Boolean method "pending" should read as a predicate (is/has/can/...).'],
            144 => ['Boolean method "HasErrors" should read as a predicate (is/has/can/...).'],
            149 => ['Boolean method "operational" should read as a predicate (is/has/can/...).'],
            159 => ['Boolean method "launch" should read as a predicate (is/has/can/...).'],
        ]);
    }

    /**
     * Exempts methods of a structure named with an ignored suffix.
     *
     * @return void
     */
    public function testExemptsMethodsOfClassesWithAnIgnoredSuffix(): void
    {
        $this->assertErrorsOnLines('BooleanMethodNameIgnoredClass.inc', [15, 21, 53, 61, 67]);
    }

    /**
     * Exempts methods carrying an Override attribute, however written; the
     * declaring interface is still checked.
     *
     * @return void
     */
    public function testExemptsMethodsCarryingAnOverrideAttribute(): void
    {
        $this->assertErrorsOnLines('BooleanMethodNameOverride.inc', [9, 46, 52, 58, 66]);
    }

    /**
     * Add a project command verb as a consumer ruleset would, and exempt the
     * fixture's policy classes.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return [
            'commandVerbs'         => [...(new BooleanMethodNameSniff)->commandVerbs, 'frobnicate'],
            'ignoredClassSuffixes' => ['', 'Policy'],
        ];
    }
}
