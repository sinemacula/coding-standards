<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Classes;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;

/**
 * Readonly class sniff.
 *
 * A concrete class that holds at least one property and whose every property
 * (declared or constructor-promoted) is `readonly` must be declared a
 * `readonly` class rather than repeating the modifier on each property. A class
 * with no properties, with any mutable or static property, or already declared
 * `readonly` is left alone; a static property can never be readonly, so its
 * presence keeps the class mutable. Abstract classes are exempt because a
 * readonly class forces the modifier onto every descendant. Configured
 * mutable-entity bases (e.g. Eloquent's Model) and test classes are exempt too.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireReadonlyClassSniff implements Sniff
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
        return [T_CLASS];
    }

    /**
     * Process a class declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $properties = $phpcsFile->getClassProperties($stackPtr);

        if ($properties['is_readonly'] || $properties['is_abstract']) {
            return;
        }

        if ($this->isInIgnoredClass($phpcsFile, $stackPtr) || $this->isTestClass($phpcsFile, $stackPtr)) {
            return;
        }

        $readonly = $this->propertyReadonlyFlags($phpcsFile, $stackPtr);

        if ($readonly === [] || in_array(false, $readonly, true)) {
            return;
        }

        $phpcsFile->addError(
            'Class "%s" must be declared readonly; all of its properties are readonly.',
            $stackPtr,
            'NotReadonly',
            [$phpcsFile->getDeclarationName($stackPtr)],
        );
    }

    /**
     * Whether the class extends a configured mutable-entity base.
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
        $parent = $phpcsFile->findExtendedClassName($stackPtr);

        return $parent !== false && in_array($parent, $this->ignoredParentClasses, true);
    }

    /**
     * Collect the readonly flag of every property the class declares directly,
     * both plain member properties and constructor-promoted ones.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $classPtr
     * @return list<bool>
     */
    private function propertyReadonlyFlags(File $phpcsFile, int $classPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$classPtr]['scope_opener'] ?? null;
        $closer = $tokens[$classPtr]['scope_closer'] ?? null;

        if ($opener === null || $closer === null) {
            return []; // @codeCoverageIgnore
        }

        $flags = [];

        for ($i = $opener; $i < $closer; $i++) {
            array_push($flags, ...$this->propertyFlagsAt($phpcsFile, $i, $classPtr));
        }

        return $flags;
    }

    /**
     * The readonly flags a single token contributes: one for a member property
     * declared directly on the class, one per promoted parameter when the token
     * is the class's constructor, and none otherwise.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @param  int  $classPtr
     * @return list<bool>
     */
    private function propertyFlagsAt(File $phpcsFile, int $stackPtr, int $classPtr): array
    {
        $code = $phpcsFile->getTokens()[$stackPtr]['code'];

        if ($code === T_VARIABLE) {
            $flag = $this->declaredMemberReadonly($phpcsFile, $stackPtr, $classPtr);

            return $flag === null ? [] : [$flag];
        }

        if ($code === T_FUNCTION && $this->isConstructor($phpcsFile, $stackPtr, $classPtr)) {
            return $this->promotedMemberReadonly($phpcsFile, $stackPtr);
        }

        return [];
    }

    /**
     * The readonly flag of a plain member property declared directly on the
     * class, or null when the variable is not such a property (a promoted
     * parameter, a local, or a member of a nested structure).
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @param  int  $classPtr
     * @return bool|null
     */
    private function declaredMemberReadonly(File $phpcsFile, int $stackPtr, int $classPtr): ?bool
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['nested_parenthesis']) || $this->isDirectlyInside($phpcsFile, $stackPtr, $classPtr) === false) {
            return null;
        }

        try {
            $properties = $phpcsFile->getMemberProperties($stackPtr);
        } catch (RuntimeException) {
            return null; // Not a member variable; nothing to weigh.
        }

        return $properties['is_readonly'];
    }

    /**
     * The readonly flag of each constructor-promoted property.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return list<bool>
     */
    private function promotedMemberReadonly(File $phpcsFile, int $stackPtr): array
    {
        $flags = [];

        foreach ($phpcsFile->getMethodParameters($stackPtr) as $parameter) {
            if (isset($parameter['property_visibility']) === false) {
                continue;
            }

            $flags[] = (bool) ($parameter['property_readonly'] ?? false);
        }

        return $flags;
    }

    /**
     * Whether the function declared at the pointer is the class's own
     * constructor, so its promoted parameters count as this class's properties.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @param  int  $classPtr
     * @return bool
     */
    private function isConstructor(File $phpcsFile, int $stackPtr, int $classPtr): bool
    {
        return $this->isDirectlyInside($phpcsFile, $stackPtr, $classPtr)
            && strtolower((string) $phpcsFile->getDeclarationName($stackPtr)) === '__construct';
    }

    /**
     * Whether the token sits directly inside the given class rather than in a
     * method or a further-nested structure within it. Scopes are recorded from
     * outermost to innermost, so the token's innermost enclosing scope is the
     * last of its conditions; the token belongs to the class when that scope is
     * the class itself.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @param  int  $classPtr
     * @return bool
     */
    private function isDirectlyInside(File $phpcsFile, int $stackPtr, int $classPtr): bool
    {
        $conditions = $phpcsFile->getTokens()[$stackPtr]['conditions'];

        return is_array($conditions) && array_key_last($conditions) === $classPtr;
    }
}
