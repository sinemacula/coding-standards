<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Enum case naming sniff.
 *
 * Ensures every enum case is declared in SCREAMING_SNAKE_CASE, matching the
 * Sine Macula house convention for enum cases.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class ValidEnumCaseNameSniff implements Sniff
{
    /** The pattern a valid enum case name must match. */
    private const string NAME_PATTERN = '/^[A-Z][A-Z0-9_]*$/';

    /**
     * Register the tokens this sniff listens for.
     *
     * PHP_CodeSniffer re-tokenises an enum `case` to its own `T_ENUM_CASE`
     * token, so switch cases (which stay `T_CASE`) are never seen here.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_ENUM_CASE];
    }

    /**
     * Process an enum case token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens  = $phpcsFile->getTokens();
        $namePtr = $phpcsFile->findNext(T_STRING, $stackPtr + 1);

        if ($namePtr === false) {
            return; // @codeCoverageIgnore
        }

        $name = $tokens[$namePtr]['content'];

        if (preg_match(self::NAME_PATTERN, $name) === 1) {
            return;
        }

        $phpcsFile->addError(
            'Enum case "%s" must be declared in SCREAMING_SNAKE_CASE.',
            $namePtr,
            'NotUpperSnakeCase',
            [$name],
        );
    }
}
