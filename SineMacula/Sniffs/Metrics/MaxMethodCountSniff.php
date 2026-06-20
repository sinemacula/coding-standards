<?php

namespace SineMacula\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Method count sniff.
 *
 * Caps the number of methods declared directly on a class, interface, trait or
 * enum. A structure that legitimately needs more can opt out with a native
 * `// phpcs:ignore SineMacula.Metrics.MaxMethodCount.TooManyMethods` directive.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class MaxMethodCountSniff implements Sniff
{
    /** The maximum number of methods allowed on a single structure. */
    public int $maxMethods = 20;

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM];
    }

    /**
     * Process an object structure token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return; // @codeCoverageIgnore
        }

        $count = $this->countMethods($tokens, $stackPtr);

        if ($count > $this->maxMethods) {
            $phpcsFile->addError(
                'Structure declares %d methods; the maximum is %d.',
                $stackPtr,
                'TooManyMethods',
                [$count, $this->maxMethods]
            );
        }
    }

    /**
     * Count the methods declared directly on the structure.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $stackPtr
     * @return int
     */
    private function countMethods(array $tokens, int $stackPtr): int
    {
        $count = 0;

        for ($i = $tokens[$stackPtr]['scope_opener'] + 1; $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            if ($tokens[$i]['code'] === T_FUNCTION && array_key_last($tokens[$i]['conditions']) === $stackPtr) {
                $count++;
            }
        }

        return $count;
    }
}
