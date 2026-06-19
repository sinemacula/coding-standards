<?php

namespace SineMacula\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Final class sniff.
 *
 * Requires every concrete class to be `final`. Classes designed for extension
 * must be `abstract`; the rare concrete class that must remain extendable can
 * opt out with an `@inheritable` docblock tag.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireFinalClassSniff implements Sniff
{
    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_CLASS];
    }

    /**
     * Process a class declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $properties = $phpcsFile->getClassProperties($stackPtr);

        if ($properties['is_abstract'] || $properties['is_final']) {
            return;
        }

        if ($this->isMarkedInheritable($phpcsFile, $stackPtr)) {
            return;
        }

        $phpcsFile->addError(
            'Class "%s" must be declared final or abstract (or marked @inheritable).',
            $stackPtr,
            'NotFinal',
            [$phpcsFile->getDeclarationName($stackPtr)]
        );
    }

    /**
     * Determine whether the class docblock carries an @inheritable tag.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isMarkedInheritable(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $before = $phpcsFile->findPrevious(
            [T_WHITESPACE, T_ABSTRACT, T_FINAL, T_READONLY],
            $stackPtr - 1,
            null,
            true
        );

        if ($before === false || $tokens[$before]['code'] !== T_DOC_COMMENT_CLOSE_TAG) {
            return false;
        }

        for ($i = $tokens[$before]['comment_opener']; $i < $before; $i++) {
            if ($tokens[$i]['code'] === T_DOC_COMMENT_TAG
                && strtolower($tokens[$i]['content']) === '@inheritable'
            ) {
                return true;
            }
        }

        return false;
    }
}
