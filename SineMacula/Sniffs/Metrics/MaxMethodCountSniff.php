<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use SineMacula\CodingStandards\Sniffs\AbstractMetricSniff;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;

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
final class MaxMethodCountSniff extends AbstractMetricSniff
{
    use DetectsTestClasses;

    /** @var int The maximum number of methods allowed on a single structure. */
    public int $maxMethods = 20;

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM];
    }

    /**
     * Skip test classes, which legitimately declare many methods, then defer
     * to the base metric check.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        if ($this->isTestClass($phpcsFile, $stackPtr)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Count the methods declared directly on the structure.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $stackPtr
     * @return int
     */
    #[\Override]
    protected function measure(array $tokens, int $stackPtr): int
    {
        $count = 0;

        for ($i = $tokens[$stackPtr]['scope_opener'] + 1; $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            if ($tokens[$i]['code'] !== T_FUNCTION || array_key_last($tokens[$i]['conditions']) !== $stackPtr) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    /**
     * The maximum number of methods permitted.
     *
     * @return int
     */
    #[\Override]
    protected function limit(): int
    {
        return $this->maxMethods;
    }

    /**
     * The error message.
     *
     * @return string
     */
    #[\Override]
    protected function message(): string
    {
        return 'Structure declares %d methods; the maximum is %d.';
    }

    /**
     * The sniff error code.
     *
     * @return string
     */
    #[\Override]
    protected function errorCode(): string
    {
        return 'TooManyMethods';
    }
}
