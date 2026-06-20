<?php

declare(strict_types = 1);

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
    /** @var int The maximum number of significant lines allowed in a body. */
    public int $maxLength = 50;

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
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
    #[\Override]
    protected function measure(array $tokens, int $stackPtr): int
    {
        $lines = [];

        for ($i = $tokens[$stackPtr]['scope_opener'] + 1; $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) !== false) {
                continue;
            }

            $lines[$tokens[$i]['line']] = true;
        }

        return count($lines);
    }

    /**
     * The maximum number of significant lines permitted.
     *
     * @return int
     */
    #[\Override]
    protected function limit(): int
    {
        return $this->maxLength;
    }

    /**
     * The error message.
     *
     * @return string
     */
    #[\Override]
    protected function message(): string
    {
        return 'Method body has %d lines; the maximum is %d.';
    }

    /**
     * The sniff error code.
     *
     * @return string
     */
    #[\Override]
    protected function errorCode(): string
    {
        return 'TooLong';
    }
}
