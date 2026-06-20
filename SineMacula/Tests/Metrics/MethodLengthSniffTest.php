<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Metrics;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the method length sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class MethodLengthSniffTest extends AbstractSniffTestCase
{
    /**
     * Only methods whose body exceeds the line limit should be flagged.
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
