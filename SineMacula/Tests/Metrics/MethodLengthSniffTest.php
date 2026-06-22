<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Metrics;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\CodingStandards\Sniffs\AbstractMetricSniff;
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
