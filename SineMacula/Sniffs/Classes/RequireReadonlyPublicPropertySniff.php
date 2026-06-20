<?php

namespace SineMacula\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Throwable;

/**
 * Readonly public property sniff.
 *
 * Public properties (declared or constructor-promoted) must be `readonly`.
 * Mutable public state breaks encapsulation; the legitimate data-holder case is
 * expressed with `public readonly`. Static properties are left to the mutable
 * static state rule, and non-public properties are unaffected.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireReadonlyPublicPropertySniff implements Sniff
{
    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_VARIABLE, T_FUNCTION];
    }

    /**
     * Process a variable or function token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_FUNCTION) {
            $this->checkPromoted($phpcsFile, $stackPtr);

            return;
        }

        $this->checkMember($phpcsFile, $stackPtr);
    }

    /**
     * Flag a declared public, non-static, non-readonly property.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    private function checkMember(File $phpcsFile, int $stackPtr): void
    {
        try {
            $properties = $phpcsFile->getMemberProperties($stackPtr);
        } catch (Throwable) {
            return;
        }

        if ($properties['is_static'] === false
            && $properties['scope'] === 'public'
            && $properties['is_readonly'] === false
        ) {
            $phpcsFile->addError(
                'Public property "%s" must be readonly; mutable public state breaks encapsulation.',
                $stackPtr,
                'Mutable',
                [$phpcsFile->getTokens()[$stackPtr]['content']]
            );
        }
    }

    /**
     * Flag any public, non-readonly constructor-promoted property.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    private function checkPromoted(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['parenthesis_opener']) === false) {
            return; // @codeCoverageIgnore
        }

        foreach ($phpcsFile->getMethodParameters($stackPtr) as $parameter) {
            if (($parameter['property_visibility'] ?? null) === 'public'
                && ($parameter['property_readonly'] ?? false) === false
            ) {
                $phpcsFile->addError(
                    'Public property "%s" must be readonly; mutable public state breaks encapsulation.',
                    $parameter['token'],
                    'Mutable',
                    [$parameter['name']]
                );
            }
        }
    }
}
