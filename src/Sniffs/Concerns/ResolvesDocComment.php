<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

use PHP_CodeSniffer\Files\File;

/**
 * Resolves the doc comment attached to a declaration.
 *
 * Scans back over modifiers and whole attribute groups, either of which may sit
 * between the docblock and the keyword it documents.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
trait ResolvesDocComment
{
    /**
     * Resolve the declaration's doc comment close tag, or null if it has none.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @param  array<int, int|string>  $modifiers
     * @return int|null
     */
    protected function docCommentCloser(File $phpcsFile, int $stackPtr, array $modifiers = []): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $skip   = [T_WHITESPACE, ...$modifiers];
        $ptr    = $stackPtr - 1;

        while (($before = $phpcsFile->findPrevious($skip, $ptr, null, true)) !== false) {
            if ($tokens[$before]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
                return $before;
            }

            if ($tokens[$before]['code'] !== T_ATTRIBUTE_END) {
                return null;
            }

            $ptr = $tokens[$before]['attribute_opener'] - 1;
        }

        return null;
    }
}
