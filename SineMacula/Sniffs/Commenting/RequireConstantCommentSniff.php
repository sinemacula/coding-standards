<?php

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class constant doc comment sniff.
 *
 * Requires a doc comment directly above every class, interface, enum or trait
 * constant. The native phpcs variable-comment sniff covers properties but not
 * constants, so this closes that gap. Global constants are not affected.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireConstantCommentSniff implements Sniff
{
    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_CONST];
    }

    /**
     * Process a constant declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (empty($tokens[$stackPtr]['conditions'])) {
            return;
        }

        $before = $phpcsFile->findPrevious(
            [T_WHITESPACE, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL],
            $stackPtr - 1,
            null,
            true
        );

        if ($before !== false && $tokens[$before]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
            return;
        }

        $phpcsFile->addError('Class constant must have a doc comment.', $stackPtr, 'Missing');
    }
}
