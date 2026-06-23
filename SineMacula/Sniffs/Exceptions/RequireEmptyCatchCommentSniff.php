<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Exceptions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Empty catch comment sniff.
 *
 * An empty catch must carry a comment explaining the intentional swallow. A
 * documented swallow (a non-JSON-scalar fallback, an expected-exception test
 * asserting on state afterwards) is deliberate; a bare `catch (E $e) {}` is a
 * silently discarded error and a likely bug. This replaces the core
 * Generic.CodeAnalysis.EmptyStatement.DetectedCatch, which flags both forms.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireEmptyCatchCommentSniff implements Sniff
{
    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_CATCH];
    }

    /**
     * Process a catch token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener'], $tokens[$stackPtr]['scope_closer']) === false) {
            return; // @codeCoverageIgnore
        }

        $opener  = $tokens[$stackPtr]['scope_opener'];
        $closer  = $tokens[$stackPtr]['scope_closer'];
        $content = $phpcsFile->findNext(T_WHITESPACE, $opener + 1, $closer, true);

        if ($content !== false) {
            return;
        }

        $phpcsFile->addError(
            'Empty catch block must explain the intentional swallow with a comment.',
            $stackPtr,
            'Missing',
        );
    }
}
