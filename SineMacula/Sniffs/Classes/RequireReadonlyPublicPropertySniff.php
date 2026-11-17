<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Classes;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;

/**
 * Requires public properties, declared or promoted, to be `readonly`.
 *
 * Static properties, readonly classes and test classes are exempt, as are the
 * classes and properties configured through the public ignore lists.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireReadonlyPublicPropertySniff implements Sniff
{
    use DetectsTestClasses;

    /** @var array<int, string> Parent classes whose subclasses are exempt. */
    public array $ignoredParentClasses = [];

    /** @var array<string, string> Interface short names mapped to comma-separated exempt property names. */
    public array $ignoredInterfaceProperties = [];

    /** @var array<string, string> Parent class short names mapped to comma-separated exempt property names. */
    public array $ignoredParentClassProperties = [];

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
     * Determine whether the token sits in a `readonly` class.
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
     * Determine whether the token sits in a class extending an ignored parent,
     * such as a framework's mutable model base. Names match case-insensitively
     * on their short name.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isInIgnoredClass(File $phpcsFile, int $stackPtr): bool
    {
        $classPtr = $phpcsFile->getCondition($stackPtr, T_CLASS, false);
        $parent   = $classPtr === false ? false : $phpcsFile->findExtendedClassName($classPtr);

        return $parent !== false
            && in_array($this->shortName($parent), array_map($this->shortName(...), $this->ignoredParentClasses), true);
    }

    /**
     * Determine whether the property is exempt through an interface or parent
     * named directly on its own class, such as a queued job's `$tries`. Names
     * match case-insensitively on their short name.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @param  string  $property
     * @return bool
     */
    private function isIgnoredProperty(File $phpcsFile, int $stackPtr, string $property): bool
    {
        $conditions = $phpcsFile->getTokens()[$stackPtr]['conditions'];

        if (!in_array(end($conditions), [T_CLASS, T_ANON_CLASS], true)) {
            return false;
        }

        $classPtr   = (int) key($conditions);
        $interfaces = $phpcsFile->findImplementedInterfaceNames($classPtr);
        $parent     = $phpcsFile->findExtendedClassName($classPtr);

        $exempt = [
            ...$this->exemptProperties($this->ignoredInterfaceProperties, $interfaces === false ? [] : $interfaces),
            ...$this->exemptProperties($this->ignoredParentClassProperties, $parent === false ? [] : [$parent]),
        ];

        return in_array(ltrim($property, '$'), $exempt, true);
    }

    /**
     * Collect the property names an exemption map grants to the given classes.
     *
     * @param  array<string, string>  $exemptions
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    private function exemptProperties(array $exemptions, array $names): array
    {
        $names  = array_map($this->shortName(...), $names);
        $exempt = [];

        foreach ($exemptions as $name => $properties) {
            if (!in_array($this->shortName((string) $name), $names, true)) {
                continue;
            }

            $exempt = [...$exempt, ...array_map(trim(...), explode(',', $properties))];
        }

        return $exempt;
    }

    /**
     * Lowercase the last namespace segment of a name, keeping its leading
     * backslash.
     *
     * @param  string  $name
     * @return string
     */
    private function shortName(string $name): string
    {
        return strtolower((string) strrchr('\\' . $name, '\\'));
    }

    /**
     * Determine whether the token sits in a test class.
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
            // Not a member variable, e.g. a local.
            return;
        }

        if (
            $properties['is_static']      !== false
            || $properties['scope']       !== 'public'
            || $properties['is_readonly'] !== false
            || $this->isIgnoredProperty($phpcsFile, $stackPtr, $phpcsFile->getTokens()[$stackPtr]['content'])
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
                || $this->isIgnoredProperty($phpcsFile, $stackPtr, $parameter['name'])
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
