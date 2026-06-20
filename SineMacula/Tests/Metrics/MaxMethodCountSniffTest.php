<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Metrics;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the method count sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class MaxMethodCountSniffTest extends AbstractSniffTestCase
{
    /**
     * Only structures exceeding the method limit should be flagged.
     *
     * @return void
     */
    public function testFlagsStructuresWithTooManyMethods(): void
    {
        $this->assertErrorsOnLines('MaxMethodCount.inc', [5]);
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
