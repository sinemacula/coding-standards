<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that the mixed ban can be suppressed where the standard says to.
 *
 * The ban reads the types written in a doc comment, such as the value type of
 * array<string, mixed>, which a line directive cannot reach. The suppression
 * goes in the same doc comment instead.
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
     * A doc comment that suppresses the ban may carry a mixed type, and nothing
     * else in the standard objects to the directive.
     *
     * @return void
     */
    public function testTheDocCommentSuppressesTheMixedBan(): void
    {
        $this->assertStandardReports('MixedTypeSuppressed.inc', []);
    }
}
