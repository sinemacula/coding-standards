<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a native type on every class, interface, enum or trait constant, as
 * in `const string FOO = '...'`.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireConstantTypeSniff implements Sniff
{
    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_CONST];
    }

    /**
     * Process a constant declaration token.
     *
     * Global constants cannot be typed. An untyped constant has its name
     * directly after `const`.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (empty($tokens[$stackPtr]['conditions'])) {
            return;
        }

        $equalPtr = $phpcsFile->findNext(T_EQUAL, $stackPtr + 1);

        if ($equalPtr === false) {
            return; // @codeCoverageIgnore
        }

        $namePtr  = $phpcsFile->findPrevious(T_STRING, $equalPtr - 1, $stackPtr);
        $firstPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);

        if ($namePtr === false || $firstPtr !== $namePtr) {
            return;
        }

        $phpcsFile->addError(
            'Class constant "%s" must declare a type.',
            $namePtr,
            'MissingType',
            [$tokens[$namePtr]['content']],
        );
    }
}
