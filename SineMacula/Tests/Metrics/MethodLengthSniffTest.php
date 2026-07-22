<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Metrics;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\AbstractMetricSniff;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;
use SineMacula\Sniffs\Metrics\MethodLengthSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the method length sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(MethodLengthSniff::class)]
#[CoversClass(AbstractMetricSniff::class)]
#[CoversTrait(DetectsTestClasses::class)]
final class MethodLengthSniffTest extends AbstractSniffTestCase
{
    /**
     * Methods whose body exceeds the line limit are flagged; test methods not.
     *
     * @return void
     */
    public function testFlagsOverLongMethods(): void
    {
        $this->assertErrorsOnLines('MethodLength.inc', [7]);
    }

    /**
     * The error message reports the measured line count and the limit, so the
     * count must exclude the brace lines themselves.
     *
     * @return void
     */
    public function testReportsMeasuredLinesAndLimit(): void
    {
        $this->assertErrorMessagesOnLines('MethodLength.inc', [
            7 => ['Method body has 4 lines; the maximum is 3.'],
        ]);
    }

    /**
     * Blank lines and comments are excluded, so a method whose significant
     * lines sit exactly at the limit passes.
     *
     * @return void
     */
    public function testExcludesBlankLinesAndComments(): void
    {
        $this->assertErrorsOnLines('MethodLengthComments.inc', []);
    }

    /**
     * A statement sharing the opening brace's line counts towards the total.
     *
     * @return void
     */
    public function testCountsStatementOnOpeningBraceLine(): void
    {
        $this->assertErrorMessagesOnLines('MethodLengthBraceLine.inc', [
            7 => ['Method body has 4 lines; the maximum is 3.'],
        ]);
    }

    /**
     * A class declared inside a test method is judged on its own name, so its
     * over-long methods are still flagged.
     *
     * @return void
     */
    public function testFlagsMethodsOfClassesDeclaredInsideTestMethods(): void
    {
        $this->assertErrorsOnLines('MethodLengthNested.inc', [11]);
    }

    /**
     * Lower the limit so the fixture stays small.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return ['maxLength' => 3];
    }
}
