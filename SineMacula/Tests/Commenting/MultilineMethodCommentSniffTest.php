<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\MultilineMethodCommentSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the multi-line method comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(MultilineMethodCommentSniff::class)]
final class MultilineMethodCommentSniffTest extends AbstractSniffTestCase
{
    /**
     * A single-line doc comment on a class, interface or trait method is
     * flagged, whatever its modifiers; a multi-line one, an undocumented method
     * and a single-line free function are not.
     *
     * @return void
     */
    public function testFlagsSingleLineMethodComments(): void
    {
        $this->assertErrorsOnLines('MultilineMethodComment.inc', [12, 17, 31, 37]);
    }
}
