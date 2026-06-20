<?php

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Mixed constructor parameter comment sniff.
 *
 * When a constructor mixes promoted properties with plain parameters, each plain
 * parameter must carry a preceding inline comment explaining why it is not
 * promoted.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireNonPromotedParameterCommentSniff implements Sniff
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

        $params = $phpcsFile->getMethodParameters($stackPtr);

        if ($this->isMixed($params) === false) {
            return;
        }

        $lowerBound = $tokens[$stackPtr]['parenthesis_opener'];

        foreach ($params as $param) {
            $varPtr = $param['token'];

            if (isset($param['property_visibility']) === false
                && $this->hasComment($tokens, $lowerBound, $varPtr) === false
            ) {
                $phpcsFile->addError(
                    'Non-promoted parameter "%s" mixed with promoted properties must carry a preceding comment.',
                    $varPtr,
                    'Missing',
                    [$param['name']]
                );
            }

            $lowerBound = $varPtr;
        }
    }

    /**
     * Determine whether the parameters mix promoted properties and plain params.
     *
     * @param  array<int, array<string, mixed>>  $params
     * @return bool
     */
    private function isMixed(array $params): bool
    {
        $promoted = false;
        $plain    = false;

        foreach ($params as $param) {
            if (isset($param['property_visibility'])) {
                $promoted = true;
            } else {
                $plain = true;
            }
        }

        return $promoted && $plain;
    }

    /**
     * Determine whether an inline comment sits between two points.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $from
     * @param  int  $to
     * @return bool
     */
    private function hasComment(array $tokens, int $from, int $to): bool
    {
        for ($i = $from + 1; $i < $to; $i++) {
            if ($tokens[$i]['code'] === T_COMMENT) {
                return true;
            }
        }

        return false;
    }
}
