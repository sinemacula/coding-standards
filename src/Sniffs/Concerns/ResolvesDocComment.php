<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

use PHP_CodeSniffer\Files\File;

/**
 * Resolves the doc comment attached to a declaration.
 *
 * A docblock does not always sit immediately before the keyword it documents:
 * declaration modifiers (public, final, ...) and one or more attribute groups
 * (`#[\Override]`, ...) may sit between them. A naive backwards scan that only
 * steps over modifiers lands on an attribute's closing bracket and concludes
 * the declaration is undocumented. This trait scans back over whitespace, the
 * given modifiers and whole attribute groups so the docblock is found wherever
 * it legitimately sits.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
trait ResolvesDocComment
{
    /**
     * Resolve the doc comment close tag for the declaration at the pointer,
     * looking past the given modifiers and any attribute groups, or null when
     * the declaration carries no doc comment.
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

            // Anything other than an attribute group between the docblock and
            // the declaration means there is no docblock to find.
            if ($tokens[$before]['code'] !== T_ATTRIBUTE_END) {
                return null;
            }

            // Step over the whole attribute group and keep looking; the close
            // bracket carries the opener it pairs with.
            $ptr = $tokens[$before]['attribute_opener'] - 1;
        }

        return null;
    }
}
