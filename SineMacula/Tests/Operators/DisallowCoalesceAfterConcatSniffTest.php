<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Operators;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Operators\DisallowCoalesceAfterConcatSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the unreachable null coalesce sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(DisallowCoalesceAfterConcatSniff::class)]
final class DisallowCoalesceAfterConcatSniffTest extends AbstractSniffTestCase
{
    /** @var string The message rendered for an unreachable fallback. */
    private const string MESSAGE = 'The ?? fallback is unreachable because a concatenation is never null; '
        . 'parenthesise the nullable operand, as in $prefix . ($value ?? $fallback).';

    /** @var array<int, int> The fixture lines that hold an unreachable fallback. */
    private const array FLAGGED_LINES = [4, 5, 6, 9, 10, 11, 12, 16, 19];

    /**
     * A `??` whose whole left operand is a concatenation is flagged, wrapped in
     * parentheses or not, across lines, and wherever the expression sits. One
     * that applies to a nullable operand, sits inside a bracketed group, or
     * follows a looser operator is not.
     *
     * @return void
     */
    public function testFlagsACoalesceWhoseLeftOperandIsAConcatenation(): void
    {
        $this->assertErrorsOnLines('DisallowCoalesceAfterConcat.inc', self::FLAGGED_LINES);
    }

    /**
     * The message explains why the fallback is dead and how to fix it.
     *
     * @return void
     */
    public function testExplainsTheFixInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines(
            'DisallowCoalesceAfterConcat.inc',
            array_fill_keys(self::FLAGGED_LINES, [self::MESSAGE]),
        );
    }
}
