<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;

/**
 * Boolean method name sniff.
 *
 * A method (or function) returning `bool` should read as a predicate. A name is
 * accepted when its first camelCase word is a copular or modal prefix (is, has,
 * can, ...), an idiomatic predicate from $allowedPredicates (e.g. successful),
 * or a verb ending in `s` (third-person: permits, passes) or `ed` (past tense:
 * succeeded, failed, expired). An imperative command verb (execute, persist,
 * guard, ...) that returns a result bool is exempt via $commandVerbs, which a
 * consuming ruleset can extend. A method may also opt out with an @imperative
 * docblock tag. Magic methods are exempt, as are the methods of a named class,
 * interface, trait or enum whose name ends with one of $ignoredClassSuffixes,
 * and a method carrying an Override attribute, whose name is set by the parent
 * or interface it overrides and is checked where that declares it.
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
     * The leading camelCase word of a method name.
     *
     * @param  string  $name
     * @return string
     */
    private function firstWord(string $name): string
    {
        return preg_match('/^[a-z]+/', $name, $matches) === 1 ? $matches[0] : '';
    }

    /**
     * Whether the name is an imperative command verb, not a predicate.
     *
     * @param  string  $name
     * @return bool
     */
    private function isCommandVerb(string $name): bool
    {
        return in_array($this->firstWord($name), $this->commandVerbs, true);
    }

    /**
     * Whether the name reads as a predicate: a copular/modal prefix, an
     * idiomatic predicate, or a verb ending in `s` (third-person) or `ed` (past
     * tense).
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
     * Whether the method is exempt whatever its name: it belongs to an ignored
     * class, overrides a parent or interface method, or opts out by tag.
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
     * Whether the function is a method of a named class, interface, trait or
     * enum whose name ends with a configured suffix.
     *
     * Methods whose names a framework resolves by convention (e.g. the ability
     * methods of a Laravel policy) cannot be renamed into predicates. An
     * anonymous class has no name and so is never exempt.
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
     * Whether the method carries an Override attribute, its name being set by
     * the parent or interface it overrides.
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
            ($closer = $phpcsFile->findPrevious([T_WHITESPACE, ...self::MODIFIERS], $ptr, null, true)) !== false
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
     * The lowercased names of the attributes in the group between the
     * brackets, without a leading separator or their arguments.
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
        $group = $phpcsFile->getTokensAsString($opener + 1, $closer - $opener - 1);
        $names = explode(',', (string) preg_replace('/\((?:[^()]++|(?R))*\)/', '', $group));

        return array_map(static fn (string $name): string => strtolower(ltrim(trim($name), '\\')), $names);
    }

    /**
     * Whether the method docblock carries an @imperative opt-out tag.
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
