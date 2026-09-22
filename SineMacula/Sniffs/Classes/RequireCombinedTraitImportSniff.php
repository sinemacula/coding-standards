<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Combined trait import sniff.
 *
 * Requires a structure to import its traits in one statement, `use A, B;`,
 * rather than one statement per trait. The set of behaviour a class mixes in is
 * then a single thing to read, and adding to it is an edit to that statement
 * rather than a new line that reviews as unrelated.
 *
 * Every import after the first is reported, wherever it sits in the body, so
 * the split form cannot survive by separating the statements. The report is not
 * fixable: merging them is an edit to a line whose order is the author's to
 * choose, and a fixable report here could be undone by a formatter on the next
 * pass.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireCombinedTraitImportSniff implements Sniff
{
    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_CLASS, T_TRAIT, T_ENUM, T_ANON_CLASS];
    }

    /**
     * Process a structure declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $imports = $this->traitImports($phpcsFile, $stackPtr);

        foreach (array_slice($imports, 1) as $import) {
            $phpcsFile->addError(
                'Traits must be imported in a single use statement.',
                $import,
                'NotCombined',
            );
        }
    }

    /**
     * The trait imports declared directly in the structure's body, in source
     * order. An import counts only where this structure is its innermost scope,
     * so a closure's own use clause and an inner structure's imports are never
     * read as this one's.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return array<int, int>
     */
    private function traitImports(File $phpcsFile, int $stackPtr): array
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener'], $tokens[$stackPtr]['scope_closer']) === false) {
            return [];
        }

        $imports = [];
        $closer  = $tokens[$stackPtr]['scope_closer'];
        $pointer = $tokens[$stackPtr]['scope_opener'];

        while (($pointer = $phpcsFile->findNext(T_USE, $pointer + 1, $closer)) !== false) {
            if (array_key_last($tokens[$pointer]['conditions']) !== $stackPtr) {
                continue;
            }

            $imports[] = $pointer;
        }

        return $imports;
    }
}
