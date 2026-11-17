<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Base sniff flagging a scoped structure whose measure exceeds a limit.
 *
 * Lives outside the scanned Sniffs tree so it is never registered as a sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class AbstractMetricSniff implements Sniff
{
    /**
     * Process a structure token, flagging when its measure exceeds the limit.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        $value = $this->measure($tokens, $stackPtr);

        if ($value <= $this->limit()) {
            return;
        }

        $phpcsFile->addError($this->message(), $stackPtr, $this->errorCode(), [$value, $this->limit()]);
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
