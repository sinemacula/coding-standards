<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Exceptions\RequireEmptyCatchCommentSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the empty catch comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireEmptyCatchCommentSniff::class)]
final class RequireEmptyCatchCommentSniffTest extends AbstractSniffTestCase
{
    /**
     * Flags an empty catch with no comment, however tight the comment sits.
     *
     * @return void
     */
    public function testFlagsUncommentedEmptyCatch(): void
    {
        $this->assertErrorsOnLines('RequireEmptyCatchComment.inc', [11, 37]);
    }
}
