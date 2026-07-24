<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;

/**
 * Fully qualified constant type sniff.
 *
 * A namespaced class name used as the type of a class, interface, enum or trait
 * constant (PHP 8.3+) must be imported and referenced by its short name, not
 * written inline as a fully qualified name: `const ErrorCode CODE = ...` behind
 * a `use` statement, never `const \App\Contracts\ErrorCode CODE = ...`. Global
 * namespace names such as `\Throwable` stay fully qualified, mirroring how
 * inline fully qualified names are treated in the rest of a file. Only the type
 * is examined; the value on the far side of the equals sign is left alone.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class DisallowFullyQualifiedConstantTypeSniff implements Sniff
{
    use ResolvesQualifiedNames;

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_CONST];
    }

    /**
     * Process a constant declaration token.
     *
     * The declaration up to its assignment is examined: the `const` keyword,
     * the type, and the constant name. The keyword and the name are not names
     * to import (the name is a bare identifier), and the value beyond the
     * assignment is out of range, so scanning this span reports exactly the
     * type names. A global (untyped) constant contributes only its name and is
     * unaffected.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $equalPtr = $phpcsFile->findNext(T_EQUAL, $stackPtr);

        if ($equalPtr === false) {
            return; // @codeCoverageIgnore
        }

        $this->reportFullyQualifiedNames($phpcsFile, $stackPtr, $equalPtr);
    }

    /**
     * Report every fully qualified, namespaced name in the span that runs from
     * the `const` keyword up to (but excluding) the assignment.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $start
     * @param  int  $end
     * @return void
     */
    private function reportFullyQualifiedNames(File $phpcsFile, int $start, int $end): void
    {
        $tokens = $phpcsFile->getTokens();
        $codes  = $this->nameTokenCodes();
        $ptr    = $start;

        while ($ptr < $end) {
            if (in_array($tokens[$ptr]['code'], $codes, true) === false) {
                $ptr++;

                continue;
            }

            $namePtr = $ptr;
            $name    = '';

            while ($ptr < $end && in_array($tokens[$ptr]['code'], $codes, true)) {
                $name .= $tokens[$ptr]['content'];
                $ptr++;
            }

            if ($this->isNamespacedFullyQualifiedName($name) === false) {
                continue;
            }

            $phpcsFile->addError(
                'Class constant type "%s" must be imported and referenced by its short name.',
                $namePtr,
                'FullyQualified',
                [$name],
            );
        }
    }

    /**
     * Whether a reconstructed name is fully qualified (a leading namespace
     * separator) and namespaced (at least one further separator), so it names a
     * class outside the global namespace that should be imported.
     *
     * @param  string  $name
     * @return bool
     */
    private function isNamespacedFullyQualifiedName(string $name): bool
    {
        return str_starts_with($name, '\\') && substr_count($name, '\\') >= 2;
    }
}
