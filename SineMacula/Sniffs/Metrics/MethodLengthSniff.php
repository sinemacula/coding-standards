<?php

namespace SineMacula\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

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
final class MethodLengthSniff implements Sniff
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
     * Process a function declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        $length = $this->significantLines($tokens, $stackPtr);

        if ($length > $this->maxLength) {
            $phpcsFile->addError(
                'Method body has %d lines; the maximum is %d.',
                $stackPtr,
                'TooLong',
                [$length, $this->maxLength]
            );
        }
    }

    /**
     * Count the body lines that carry a non-comment, non-whitespace token.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $stackPtr
     * @return int
     */
    private function significantLines(array $tokens, int $stackPtr): int
    {
        $lines = [];

        for ($i = $tokens[$stackPtr]['scope_opener'] + 1; $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === false) {
                $lines[$tokens[$i]['line']] = true;
            }
        }

        return count($lines);
    }
}
