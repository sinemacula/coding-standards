<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;

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
    use ResolvesDocComment;

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
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
    #[\Override]
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
            [$phpcsFile->getDeclarationName($stackPtr)],
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
        $closer = $this->docCommentCloser($phpcsFile, $stackPtr, [T_ABSTRACT, T_FINAL, T_READONLY]);

        if ($closer === null) {
            return false;
        }

        for ($i = $tokens[$closer]['comment_opener']; $i < $closer; $i++) {
            if (
                $tokens[$i]['code']                   === T_DOC_COMMENT_TAG
                && strtolower($tokens[$i]['content']) === '@inheritable'
            ) {
                return true;
            }
        }

        return false;
    }
}
