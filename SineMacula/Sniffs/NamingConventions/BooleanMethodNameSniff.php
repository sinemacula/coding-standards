<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;

/**
 * Requires a function returning `bool` to be named as a predicate.
 *
 * The first camelCase word must be an allowed prefix or predicate, or end in
 * `s` or `ed` (`permits`, `failed`). Command verbs, magic methods, overrides,
 * methods of classes with an ignored suffix and `@imperative` methods are
 * exempt.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class BooleanMethodNameSniff implements Sniff
{
    use ResolvesDocComment;

    /** @var array<int, int|string> Function modifiers a doc comment or attribute sits above. */
    private const array MODIFIERS = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL];

    /** @var array<int, string> Copular and modal prefixes that read as predicates. */
    public array $allowedPrefixes = [
        'is', 'are', 'was', 'were', 'has', 'have', 'had', 'can', 'could',
        'should', 'shall', 'will', 'would', 'may', 'might', 'must', 'needs',
        'does',
    ];

    /** @var array<int, string> Idiomatic predicate first words that need no is/has/can prefix. */
    public array $allowedPredicates = ['successful'];

    /** @var array<int, string> Imperative command verbs that may return a result bool. */
    public array $commandVerbs = [
        'execute', 'run', 'handle', 'process', 'perform', 'persist', 'save',
        'store', 'write', 'read', 'load', 'fetch', 'delete', 'remove', 'forget',
        'flush', 'purge', 'clear', 'reset', 'refresh', 'sync', 'send', 'dispatch',
        'emit', 'apply', 'guard', 'validate', 'verify', 'authorize', 'ensure',
        'assert', 'register', 'boot', 'build', 'make', 'resolve', 'render',
        'compute', 'calculate', 'expose', 'parse', 'format', 'transform', 'toggle',
        'rotate',
    ];

    /** @var array<int, string> Class name suffixes whose methods are exempt (e.g. Policy). */
    public array $ignoredClassSuffixes = [];

    /**
     * Register the tokens this sniff listens for.
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
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $properties = $phpcsFile->getMethodProperties($stackPtr);

        if (ltrim((string) $properties['return_type'], '?') !== 'bool') {
            return;
        }

        $name = $phpcsFile->getDeclarationName($stackPtr);

        if ($name === null || str_starts_with($name, '__')) {
            return;
        }

        if ($this->isExempt($phpcsFile, $stackPtr) || $this->isCommandVerb($name) || $this->isPredicate($name)) {
            return;
        }

        $phpcsFile->addError(
            'Boolean method "%s" should read as a predicate (is/has/can/...).',
            $stackPtr,
            'NotPredicate',
            [$name],
        );
    }

    /**
     * Read the leading camelCase word of a method name.
     *
     * @param  string  $name
     * @return string
     */
    private function firstWord(string $name): string
    {
        return preg_match('/^[a-z]+/', $name, $matches) === 1 ? $matches[0] : '';
    }

    /**
     * Determine whether the name starts with a command verb.
     *
     * @param  string  $name
     * @return bool
     */
    private function isCommandVerb(string $name): bool
    {
        return in_array($this->firstWord($name), $this->commandVerbs, true);
    }

    /**
     * Determine whether the name reads as a predicate.
     *
     * @param  string  $name
     * @return bool
     */
    private function isPredicate(string $name): bool
    {
        $first = $this->firstWord($name);

        return in_array($first, $this->allowedPrefixes, true)
            || in_array($first, $this->allowedPredicates, true)
            || str_ends_with($first, 's')
            || str_ends_with($first, 'ed');
    }

    /**
     * Determine whether the method is exempt whatever its name.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isExempt(File $phpcsFile, int $stackPtr): bool
    {
        return $this->isInIgnoredClass($phpcsFile, $stackPtr)
            || $this->isOverride($phpcsFile, $stackPtr)
            || $this->isMarkedImperative($phpcsFile, $stackPtr);
    }

    /**
     * Determine whether the method belongs to a named structure whose name ends
     * with an ignored suffix, for framework classes that fix method names by
     * convention.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isInIgnoredClass(File $phpcsFile, int $stackPtr): bool
    {
        $conditions = $phpcsFile->getTokens()[$stackPtr]['conditions'];

        if (!in_array(end($conditions), [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            return false;
        }

        $name = $phpcsFile->getDeclarationName((int) key($conditions));

        foreach ($this->ignoredClassSuffixes as $suffix) {
            if ($name !== null && $suffix !== '' && str_ends_with($name, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the method carries an Override attribute, its name
     * being checked where the parent declares it.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isOverride(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $ptr    = $stackPtr - 1;

        while (
            ($closer = $phpcsFile->findPrevious([T_WHITESPACE, T_COMMENT, ...self::MODIFIERS], $ptr, null, true)) !== false
            && $tokens[$closer]['code'] === T_ATTRIBUTE_END
        ) {
            $ptr = $tokens[$closer]['attribute_opener'];

            if (in_array('override', $this->attributeNames($phpcsFile, $ptr, $closer), true)) {
                return true;
            }

            $ptr--;
        }

        return false;
    }

    /**
     * Read the lowercased attribute names in the group, without a leading
     * separator, arguments or comments.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $opener
     * @param  int  $closer
     * @return list<string>
     *
     * @SuppressWarnings("php:S5842")
     */
    private function attributeNames(File $phpcsFile, int $opener, int $closer): array
    {
        $tokens = $phpcsFile->getTokens();
        $group  = '';

        for ($i = $opener + 1; $i < $closer; $i++) {
            if ($tokens[$i]['code'] === T_COMMENT) {
                continue;
            }

            $group .= $tokens[$i]['content'];
        }

        $names = explode(',', (string) preg_replace('/\((?:[^()]++|(?R))*\)/', '', $group));

        return array_map(static fn (string $name): string => strtolower(ltrim(trim($name), '\\')), $names);
    }

    /**
     * Determine whether the method docblock carries an @imperative tag.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isMarkedImperative(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $this->docCommentCloser($phpcsFile, $stackPtr, self::MODIFIERS);

        if ($closer === null) {
            return false;
        }

        for ($i = $tokens[$closer]['comment_opener']; $i < $closer; $i++) {
            if (
                $tokens[$i]['code']                   === T_DOC_COMMENT_TAG
                && strtolower($tokens[$i]['content']) === '@imperative'
            ) {
                return true;
            }
        }

        return false;
    }
}
