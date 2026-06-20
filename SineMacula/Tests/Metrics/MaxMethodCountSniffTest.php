<?php

namespace SineMacula\Tests\Metrics;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the method count sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class MaxMethodCountSniffTest extends AbstractSniffTestCase
{
    /**
     * Lower the limit so the fixture stays small.
     *
     * @return array<string, mixed>
     */
    protected function sniffProperties(): array
    {
        return ['maxMethods' => 2];
    }

    /**
     * Only structures exceeding the method limit should be flagged.
     *
     * @return void
     */
    public function testFlagsStructuresWithTooManyMethods(): void
    {
        $this->assertErrorsOnLines('MaxMethodCount.inc', [5]);
    }
}
