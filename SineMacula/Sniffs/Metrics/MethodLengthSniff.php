<?php

namespace SineMacula\Sniffs\Metrics;

use PHP_CodeSniffer\Util\Tokens;
use SineMacula\CodingStandards\Sniffs\AbstractMetricSniff;

/**
 * Method length sniff.
 *
 * Caps the number of significant lines (blank lines and comments excluded) in a
 * function or method body. A method that legitimately needs more can opt out
 * with a native `// phpcs:ignore SineMacula.Metrics.MethodLength.TooLong`
 * directive.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class MethodLengthSniff extends AbstractMetricSniff
{
    /** The maximum number of significant lines allowed in a body. */
    public int $maxLength = 50;

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * Count the body lines that carry a non-comment, non-whitespace token.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $stackPtr
     * @return int
     */
    protected function measure(array $tokens, int $stackPtr): int
    {
        $lines = [];

        for ($i = $tokens[$stackPtr]['scope_opener'] + 1; $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === false) {
                $lines[$tokens[$i]['line']] = true;
            }
        }

        return count($lines);
    }

    /**
     * The maximum number of significant lines permitted.
     *
     * @return int
     */
    protected function limit(): int
    {
        return $this->maxLength;
    }

    /**
     * The error message.
     *
     * @return string
     */
    protected function message(): string
    {
        return 'Method body has %d lines; the maximum is %d.';
    }

    /**
     * The sniff error code.
     *
     * @return string
     */
    protected function errorCode(): string
    {
        return 'TooLong';
    }
}
