<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Metrics;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\AbstractMetricSniff;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;
use SineMacula\Sniffs\Metrics\MaxMethodCountSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the method count sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(MaxMethodCountSniff::class)]
#[CoversClass(AbstractMetricSniff::class)]
#[CoversTrait(DetectsTestClasses::class)]
final class MaxMethodCountSniffTest extends AbstractSniffTestCase
{
    /**
     * Structures exceeding the method limit are flagged; test classes are not.
     *
     * @return void
     */
    public function testFlagsStructuresWithTooManyMethods(): void
    {
        $this->assertErrorsOnLines('MaxMethodCount.inc', [5]);
    }

    /**
     * A method whose declaration directly follows the opening brace is still
     * counted.
     *
     * @return void
     */
    public function testCountsMethodDeclaredDirectlyAfterOpeningBrace(): void
    {
        $this->assertErrorMessagesOnLines('MaxMethodCountBraceLine.inc', [
            5 => ['Structure declares 3 methods; the maximum is 2.'],
        ]);
    }

    /**
     * Lower the limit so the fixture stays small.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return ['maxMethods' => 2];
    }
}
