<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests the threshold at which a comment is taken for commented-out code.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class CommentedOutCodeTest extends AbstractStandardTestCase
{
    /**
     * A label with a colon and its limits in brackets reads as prose, while a
     * commented-out statement or return is still reported.
     *
     * @return void
     */
    public function testWarnsOnCodeButNotOnALabelWithItsLimits(): void
    {
        $this->assertStandardWarns('CommentedOutCode.inc', [
            28 => ['Squiz.PHP.CommentedOutCode.Found'],
            31 => ['Squiz.PHP.CommentedOutCode.Found'],
        ]);
    }

    /**
     * The fixture is otherwise accepted outright.
     *
     * @return void
     */
    public function testAcceptsTheFixtureOtherwise(): void
    {
        $this->assertStandardReports('CommentedOutCode.inc', []);
    }
}
