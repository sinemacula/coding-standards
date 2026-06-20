<?php

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Promoted property doc comment sniff.
 *
 * Requires a doc comment directly above every constructor-promoted property, so
 * promoted properties are documented like any other property.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequirePromotedPropertyCommentSniff implements Sniff
{
    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * Process a function declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['parenthesis_opener']) === false) {
            return; // @codeCoverageIgnore
        }

        $lowerBound = $tokens[$stackPtr]['parenthesis_opener'];

        foreach ($phpcsFile->getMethodParameters($stackPtr) as $param) {
            $varPtr = $param['token'];

            if (isset($param['property_visibility'])
                && $this->hasDocComment($tokens, $lowerBound, $varPtr) === false
            ) {
                $phpcsFile->addError(
                    'Promoted property "%s" must have a doc comment.',
                    $varPtr,
                    'Missing',
                    [$param['name']]
                );
            }

            $lowerBound = $varPtr;
        }
    }

    /**
     * Determine whether a doc comment sits between two points.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $from
     * @param  int  $to
     * @return bool
     */
    private function hasDocComment(array $tokens, int $from, int $to): bool
    {
        for ($i = $from + 1; $i < $to; $i++) {
            if ($tokens[$i]['code'] === T_DOC_COMMENT_OPEN_TAG) {
                return true;
            }
        }

        return false;
    }
}
