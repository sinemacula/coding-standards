<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Global function naming sniff.
 *
 * Ensures every global (non-member) function is declared in snake_case,
 * matching the naming convention used by PHP's own global functions.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class ValidGlobalFunctionNameSniff implements Sniff
{
    /** @var string The pattern a valid global function name must match. */
    private const string NAME_PATTERN = '/^[a-z][a-z0-9_]*$/';

    /**
     * Register the tokens this sniff listens for.
     *
     * Closures and arrow functions use their own tokens (T_CLOSURE / T_FN), so
     * only named function declarations reach this sniff.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * Process a function declaration token.
     *
     * Methods carry a class/interface/trait/enum scope condition; a global
     * function has none, which is how the two are told apart.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (empty($tokens[$stackPtr]['conditions']) === false) {
            return;
        }

        $name = $phpcsFile->getDeclarationName($stackPtr);

        if ($name === null || preg_match(self::NAME_PATTERN, $name) === 1) {
            return;
        }

        $phpcsFile->addError(
            'Global function "%s" must be declared in snake_case.',
            $stackPtr,
            'NotSnakeCase',
            [$name],
        );
    }
}
