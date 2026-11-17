<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a structure to import all its traits in one `use A, B;` statement.
 *
 * Every import after the first is reported. The report is not fixable, since
 * the merged order is the author's to choose.
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
     * Collect the trait imports declared directly in the structure's body,
     * skipping closure use clauses and nested structures.
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
