<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that the mixed ban can be suppressed where the standard says to.
 *
 * A function's doc comment carries the suppression; a single-line member doc
 * comment takes a line directive above it.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class MixedTypeSuppressionTest extends AbstractStandardTestCase
{
    /**
     * Both suppression forms are accepted by the rest of the standard.
     *
     * @return void
     */
    public function testTheDocCommentSuppressesTheMixedBan(): void
    {
        $this->assertStandardReports('MixedTypeSuppressed.inc', []);
    }
}
