<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that the mixed ban can be suppressed where the standard says to.
 *
 * The ban reads the types written in a doc comment, such as the value type of
 * array<string, mixed>, which a line directive cannot reach further down a
 * function's doc comment, so the suppression goes in that doc comment. A
 * member's single-line doc comment does not allow that tag, and a line
 * directive directly above it reaches the type instead.
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
     * A function doc comment that suppresses the ban, and a member doc comment
     * under a line directive, may carry a mixed type, and nothing else in the
     * standard objects to either directive.
     *
     * @return void
     */
    public function testTheDocCommentSuppressesTheMixedBan(): void
    {
        $this->assertStandardReports('MixedTypeSuppressed.inc', []);
    }
}
