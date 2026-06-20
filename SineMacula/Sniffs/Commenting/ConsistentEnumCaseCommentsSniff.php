<?php

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Enum case comment consistency sniff.
 *
 * Enum cases need no doc comment, but documentation must be all-or-nothing: if
 * any case in an enum is documented, every case must be. (The enum itself still
 * requires a docblock - that is handled by the class comment sniff.)
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class ConsistentEnumCaseCommentsSniff implements Sniff
{
    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_ENUM];
    }

    /**
     * Process an enum declaration token.
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

        $cases      = $this->cases($phpcsFile, $stackPtr);
        $documented = count(array_filter($cases));

        if ($documented === 0 || $documented === count($cases)) {
            return;
        }

        foreach ($cases as $casePtr => $hasComment) {
            if ($hasComment === false) {
                $phpcsFile->addError(
                    'Enum case must have a doc comment because other cases in this enum are documented.',
                    $casePtr,
                    'Inconsistent'
                );
            }
        }
    }

    /**
     * Map each enum case to whether it carries a doc comment.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return array<int, bool>
     */
    private function cases(File $phpcsFile, int $stackPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $cases  = [];

        for ($i = $tokens[$stackPtr]['scope_opener'] + 1; $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            if ($tokens[$i]['code'] === T_ENUM_CASE && array_key_last($tokens[$i]['conditions']) === $stackPtr) {
                $cases[$i] = $this->hasDocComment($phpcsFile, $i);
            }
        }

        return $cases;
    }

    /**
     * Determine whether a doc comment sits directly above an enum case.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $casePtr
     * @return bool
     */
    private function hasDocComment(File $phpcsFile, int $casePtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $before = $phpcsFile->findPrevious([T_WHITESPACE], $casePtr - 1, null, true);

        return $before !== false && $tokens[$before]['code'] === T_DOC_COMMENT_CLOSE_TAG;
    }
}
