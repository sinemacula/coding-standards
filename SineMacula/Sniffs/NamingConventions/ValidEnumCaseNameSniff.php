<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires enum case names in SCREAMING_SNAKE_CASE.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class ValidEnumCaseNameSniff implements Sniff
{
    /** @var string The pattern a valid enum case name must match. */
    private const string NAME_PATTERN = '/^[A-Z][A-Z0-9_]*$/';

    /**
     * Register the tokens this sniff listens for.
     *
     * An enum `case` tokenises as T_ENUM_CASE, so switch cases never reach it.
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
