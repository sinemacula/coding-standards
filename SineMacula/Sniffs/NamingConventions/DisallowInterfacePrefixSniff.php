<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Interface name prefix sniff.
 *
 * Forbids the Hungarian "I" prefix on interface names (IUserRepository), which
 * adds nothing over the language's own type system.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class DisallowInterfacePrefixSniff implements Sniff
{
    /** @var string Disallowed "I" interface prefix (I + uppercase letter). */
    private const string PREFIX_PATTERN = '/^I[A-Z]/';

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_INTERFACE];
    }

    /**
     * Process an interface declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $name = $phpcsFile->getDeclarationName($stackPtr);

        if ($name === null || preg_match(self::PREFIX_PATTERN, $name) !== 1) {
            return;
        }

        $phpcsFile->addError(
            'Interface "%s" must not use an "I" prefix.',
            $stackPtr,
            'Prefixed',
            [$name],
        );
    }
}
