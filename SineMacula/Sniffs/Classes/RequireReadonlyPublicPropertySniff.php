<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Classes;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;

/**
 * Readonly public property sniff.
 *
 * Public properties (declared or constructor-promoted) must be `readonly`.
 * Mutable public state breaks encapsulation; the legitimate data-holder case is
 * expressed with `public readonly`. Static properties are left to the mutable
 * static state rule, and non-public properties are unaffected. Test classes are
 * exempt (recording spies and fakes legitimately hold mutable public state).
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireReadonlyPublicPropertySniff implements Sniff
{
    use DetectsTestClasses;

    /** @var array<int, string> Parent classes whose subclasses are exempt. */
    public array $ignoredParentClasses = [];

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
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
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (
            $this->isInReadonlyClass($phpcsFile, $stackPtr)
            || $this->isInIgnoredClass($phpcsFile, $stackPtr)
            || $this->isInTestClass($phpcsFile, $stackPtr)
        ) {
            return;
        }

        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_FUNCTION) {
            $this->checkPromoted($phpcsFile, $stackPtr);

            return;
        }

        $this->checkMember($phpcsFile, $stackPtr);
    }

    /**
     * Whether the token sits in a class declared `readonly`, where every
     * property is already immutable through the class modifier (PHP 8.2+).
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isInReadonlyClass(File $phpcsFile, int $stackPtr): bool
    {
        $classPtr = $phpcsFile->getCondition($stackPtr, T_CLASS, false);

        return $classPtr !== false && $phpcsFile->getClassProperties($classPtr)['is_readonly'] === true;
    }

    /**
     * Whether the token sits in a class extending a configured ignored parent.
     *
     * Standards layered on top of this one (e.g. a framework standard) can set
     * $ignoredParentClasses to exempt mutable entity bases such as Eloquent's
     * Model, where the readonly contract does not apply.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isInIgnoredClass(File $phpcsFile, int $stackPtr): bool
    {
        $classPtr = $phpcsFile->getCondition($stackPtr, T_CLASS, false);
        $parent   = $classPtr === false ? false : $phpcsFile->findExtendedClassName($classPtr);

        return $parent !== false && in_array($parent, $this->ignoredParentClasses, true);
    }

    /**
     * Whether the token sits in a test class, where mutable public recording
     * state (spies, fakes) is legitimate rather than a smell.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isInTestClass(File $phpcsFile, int $stackPtr): bool
    {
        $classPtr = $phpcsFile->getCondition($stackPtr, T_CLASS, false);

        return $classPtr !== false && $this->isTestClass($phpcsFile, $classPtr);
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
        } catch (RuntimeException) {
            // Not a class member variable (e.g. a local); nothing to check.
            return;
        }

        if (
            $properties['is_static']      !== false
            || $properties['scope']       !== 'public'
            || $properties['is_readonly'] !== false
        ) {
            return;
        }

        $phpcsFile->addError(
            'Public property "%s" must be readonly; mutable public state breaks encapsulation.',
            $stackPtr,
            'Mutable',
            [$phpcsFile->getTokens()[$stackPtr]['content']],
        );
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
            if (
                ($parameter['property_visibility'] ?? null)   !== 'public'
                || ($parameter['property_readonly'] ?? false) !== false
            ) {
                continue;
            }

            $phpcsFile->addError(
                'Public property "%s" must be readonly; mutable public state breaks encapsulation.',
                $parameter['token'],
                'Mutable',
                [$parameter['name']],
            );
        }
    }
}
