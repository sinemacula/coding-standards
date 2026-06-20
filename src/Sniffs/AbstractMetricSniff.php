<?php

namespace SineMacula\CodingStandards\Sniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Base sniff for size metrics measured across a token scope.
 *
 * Subclasses register the structures to inspect and implement measure() to turn
 * a scope into a number; this base flags any structure whose measure exceeds the
 * configured limit. It lives under the Composer-autoloaded namespace (not the
 * PHP_CodeSniffer-scanned Sniffs tree) so it is never registered as a sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class AbstractMetricSniff implements Sniff
{
    /**
     * Process a structure token, flagging it when its measure exceeds the limit.
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

        $value = $this->measure($tokens, $stackPtr);

        if ($value > $this->limit()) {
            $phpcsFile->addError($this->message(), $stackPtr, $this->errorCode(), [$value, $this->limit()]);
        }
    }

    /**
     * Measure the structure declared at the given pointer.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $stackPtr
     * @return int
     */
    abstract protected function measure(array $tokens, int $stackPtr): int;

    /**
     * The maximum permitted value.
     *
     * @return int
     */
    abstract protected function limit(): int;

    /**
     * The error message, carrying two %d placeholders (value then limit).
     *
     * @return string
     */
    abstract protected function message(): string;

    /**
     * The sniff error code.
     *
     * @return string
     */
    abstract protected function errorCode(): string;
}
