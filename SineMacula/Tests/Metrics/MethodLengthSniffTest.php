<?php

namespace SineMacula\Tests\Metrics;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the method length sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class MethodLengthSniffTest extends AbstractSniffTestCase
{
    /**
     * Lower the limit so the fixture stays small.
     *
     * @return array<string, mixed>
     */
    protected function sniffProperties(): array
    {
        return ['maxLength' => 3];
    }

    /**
     * Only methods whose body exceeds the line limit should be flagged.
     *
     * @return void
     */
    public function testFlagsOverLongMethods(): void
    {
        $this->assertErrorsOnLines('MethodLength.inc', [7]);
    }
}
