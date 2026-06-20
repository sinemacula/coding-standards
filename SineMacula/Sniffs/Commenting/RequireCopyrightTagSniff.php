<?php

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Copyright tag sniff.
 *
 * Requires an @copyright tag in the docblock of every class, interface, enum or
 * trait. The PEAR class-comment sniff already requires the docblock and @author
 * but treats @copyright as optional, so this closes that gap. Structures with no
 * docblock are left to the class-comment sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireCopyrightTagSniff implements Sniff
{
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
        $closer = $this->docComment($phpcsFile, $stackPtr);

        if ($closer === null || $this->hasCopyrightTag($tokens, $closer)) {
            return;
        }

        $phpcsFile->addError(
            'Doc comment for "%s" must include an @copyright tag.',
            $stackPtr,
            'Missing',
            [$phpcsFile->getDeclarationName($stackPtr)]
        );
    }

    /**
     * Resolve the docblock attached to the structure, if any.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return int|null
     */
    private function docComment(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $before = $phpcsFile->findPrevious(
            [T_WHITESPACE, T_ABSTRACT, T_FINAL, T_READONLY],
            $stackPtr - 1,
            null,
            true
        );

        if ($before !== false && $tokens[$before]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
            return $before;
        }

        return null;
    }

    /**
     * Determine whether the docblock carries an @copyright tag.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $closer
     * @return bool
     */
    private function hasCopyrightTag(array $tokens, int $closer): bool
    {
        for ($i = $tokens[$closer]['comment_opener']; $i < $closer; $i++) {
            if ($tokens[$i]['code'] === T_DOC_COMMENT_TAG
                && strtolower($tokens[$i]['content']) === '@copyright'
            ) {
                return true;
            }
        }

        return false;
    }
}
