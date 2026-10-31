<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Unreachable null coalesce sniff.
 *
 * Concatenation binds tighter than `??`, so `$prefix . $value ?? $fallback`
 * reads as `($prefix . $value) ?? $fallback`. A concatenation is never null, so
 * the fallback can never run. Flags a `??` whose whole left operand is a
 * concatenation; the intended `$prefix . ($value ?? $fallback)` is not flagged.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class DisallowCoalesceAfterConcatSniff implements Sniff
{
    /** @var array<int|string, true> Tokens that end the left operand of a `??`. */
    private const array BOUNDARIES = [
        T_SEMICOLON           => true,
        T_COMMA               => true,
        T_COLON               => true,
        T_COALESCE            => true,
        T_INLINE_THEN         => true,
        T_INLINE_ELSE         => true,
        T_DOUBLE_ARROW        => true,
        T_FN_ARROW            => true,
        T_MATCH_ARROW         => true,
        T_RETURN              => true,
        T_ECHO                => true,
        T_PRINT               => true,
        T_YIELD               => true,
        T_YIELD_FROM          => true,
        T_THROW               => true,
        T_CASE                => true,
        T_LOGICAL_AND         => true,
        T_LOGICAL_OR          => true,
        T_LOGICAL_XOR         => true,
        T_EQUAL               => true,
        T_PLUS_EQUAL          => true,
        T_MINUS_EQUAL         => true,
        T_MUL_EQUAL           => true,
        T_DIV_EQUAL           => true,
        T_CONCAT_EQUAL        => true,
        T_MOD_EQUAL           => true,
        T_POW_EQUAL           => true,
        T_AND_EQUAL           => true,
        T_OR_EQUAL            => true,
        T_XOR_EQUAL           => true,
        T_SL_EQUAL            => true,
        T_SR_EQUAL            => true,
        T_COALESCE_EQUAL      => true,
        T_OPEN_TAG            => true,
        T_OPEN_TAG_WITH_ECHO  => true,
        T_OPEN_PARENTHESIS    => true,
        T_OPEN_SQUARE_BRACKET => true,
        T_OPEN_SHORT_ARRAY    => true,
        T_OPEN_CURLY_BRACKET  => true,
        T_CLOSE_CURLY_BRACKET => true,
    ];

    /** @var array<int|string, true> Operators that bind looser than `.` but tighter than `??`. */
    private const array LOOSER_THAN_CONCAT = [
        T_LESS_THAN           => true,
        T_GREATER_THAN        => true,
        T_IS_SMALLER_OR_EQUAL => true,
        T_IS_GREATER_OR_EQUAL => true,
        T_IS_EQUAL            => true,
        T_IS_NOT_EQUAL        => true,
        T_IS_IDENTICAL        => true,
        T_IS_NOT_IDENTICAL    => true,
        T_SPACESHIP           => true,
        T_BITWISE_AND         => true,
        T_BITWISE_XOR         => true,
        T_BITWISE_OR          => true,
        T_BOOLEAN_AND         => true,
        T_BOOLEAN_OR          => true,
    ];

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_COALESCE];
    }

    /**
     * Process a null coalescing operator token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (!$this->isConcatenation($phpcsFile, $stackPtr - 1)) {
            return;
        }

        $phpcsFile->addError(
            'The ?? fallback is unreachable because a concatenation is never null; parenthesise the nullable operand, as in $prefix . ($value ?? $fallback).',
            $stackPtr,
            'Found',
        );
    }

    /**
     * Whether the operand ending at the given token is a concatenation at its
     * root, looking inside a pair of parentheses that wraps all of it.
     *
     * Reads backwards to the token that ends the operand, stepping over each
     * bracketed group whole.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $ptr
     * @return bool
     */
    private function isConcatenation(File $phpcsFile, int $ptr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $found  = false;
        $parts  = 0;
        $group  = null;

        while (!isset(self::BOUNDARIES[$tokens[$ptr]['code']])) {
            $code = $tokens[$ptr]['code'];

            if (isset(self::LOOSER_THAN_CONCAT[$code])) {
                return false;
            }

            $found = $found || $code === T_STRING_CONCAT;
            $parts += isset(Tokens::$emptyTokens[$code]) ? 0 : 1;

            $opener = $this->groupOpener($tokens[$ptr]);

            if ($opener !== null) {
                $group = $ptr;
                $ptr   = $opener;
            }

            $ptr--;
        }

        if ($parts === 1 && $group !== null && $tokens[$group]['code'] === T_CLOSE_PARENTHESIS) {
            return $this->isConcatenation($phpcsFile, $group - 1);
        }

        return $found;
    }

    /**
     * The opening token of the bracketed group the given token closes, if it
     * closes one.
     *
     * @param  array<string, mixed>  $token
     * @return int|null
     */
    private function groupOpener(array $token): ?int
    {
        $opener = $token['parenthesis_opener'] ?? $token['bracket_opener'] ?? null;

        return is_int($opener) ? $opener : null;
    }
}
