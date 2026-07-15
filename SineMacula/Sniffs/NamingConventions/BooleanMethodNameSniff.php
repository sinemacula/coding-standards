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
 * docblock tag. Magic methods are exempt.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class BooleanMethodNameSniff implements Sniff
{
    use ResolvesDocComment;

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
    ];

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

        if (
            $this->isMarkedImperative($phpcsFile, $stackPtr)
            || $this->isCommandVerb($name)
            || $this->isPredicate($name)
        ) {
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
     * Whether the method docblock carries an @imperative opt-out tag.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isMarkedImperative(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $this->docCommentCloser(
            $phpcsFile,
            $stackPtr,
            [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL],
        );

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
