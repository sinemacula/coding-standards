<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;

/**
 * Requires a @throws tag for each exception a documented function's body
 * throws.
 *
 * Unlike the core throw tag sniff, extra tags are allowed, since propagated
 * checked exceptions are declared too. Throws inside closures, arrow functions
 * and anonymous classes are not the function's own.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireThrowsTagSniff implements Sniff
{
    use ResolvesDocComment, ResolvesQualifiedNames;

    /** @var array<int, int|string> Function modifiers a doc comment sits above. */
    private const array MODIFIERS = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT];

    /** @var array<int, int|string> Scopes whose throws are not the enclosing function's. */
    private const array NESTED_SCOPES = [T_CLOSURE, T_FN, T_ANON_CLASS];

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
        $tokens = $phpcsFile->getTokens();
        $closer = $this->docCommentCloser($phpcsFile, $stackPtr, self::MODIFIERS);

        if ($closer === null || !isset($tokens[$stackPtr]['scope_closer'])) {
            return;
        }

        $thrown = $this->thrownTypes($phpcsFile, $stackPtr);

        if ($thrown === null) {
            return;
        }

        $declared = $this->declaredTypes($phpcsFile, $tokens[$closer]['comment_opener']);

        if ($declared === []) {
            $phpcsFile->addError('Missing @throws tag in function comment', $closer, 'Missing');

            return;
        }

        foreach (array_filter($thrown, fn (string $type): bool => !$this->isDeclared($type, $declared)) as $type) {
            $phpcsFile->addError('Missing @throws tag for "%s" exception', $closer, 'Missing', [$type]);
        }
    }

    /**
     * Collect the exception types the function throws in its own body: an empty
     * list when none can be named, or null when it throws nothing.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $function
     * @return array<int, string>|null
     */
    private function thrownTypes(File $phpcsFile, int $function): ?array
    {
        $tokens = $phpcsFile->getTokens();
        $end    = $tokens[$function]['scope_closer'];
        $types  = null;
        $ptr    = $tokens[$function]['scope_opener'];

        while (($ptr = $phpcsFile->findNext([T_THROW, ...self::NESTED_SCOPES], $ptr + 1, $end)) !== false) {
            if ($tokens[$ptr]['code'] !== T_THROW) {
                $ptr = $tokens[$ptr]['scope_closer'] ?? $ptr;

                continue;
            }

            $types = [...($types ?? []), ...$this->throwTypes($phpcsFile, $function, $ptr)];
        }

        return $types === null ? null : array_unique($types);
    }

    /**
     * Resolve the types a throw raises, from the class it names or the catch
     * whose variable it re-throws.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $function
     * @param  int  $throw
     * @return list<string>
     */
    private function throwTypes(File $phpcsFile, int $function, int $throw): array
    {
        $tokens = $phpcsFile->getTokens();
        $next   = (int) $phpcsFile->findNext(Tokens::$emptyTokens, $throw + 1, null, true);

        if ($tokens[$next]['code'] === T_NEW) {
            $next = (int) $phpcsFile->findNext(Tokens::$emptyTokens, $next + 1, null, true);
        }

        if ($tokens[$next]['code'] === T_VARIABLE) {
            return $this->rethrownTypes($phpcsFile, $function, $next);
        }

        $name = $this->readName($phpcsFile, $next);

        return $name === '' ? [] : [$name];
    }

    /**
     * Read the types caught by the nearest preceding catch, when it binds the
     * re-thrown variable.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $function
     * @param  int  $variable
     * @return list<string>
     */
    private function rethrownTypes(File $phpcsFile, int $function, int $variable): array
    {
        $tokens = $phpcsFile->getTokens();
        $catch  = $phpcsFile->findPrevious(T_CATCH, $variable, $tokens[$function]['scope_opener']);

        if ($catch === false) {
            return [];
        }

        $opener = $tokens[$catch]['parenthesis_opener'];
        $caught = $phpcsFile->findPrevious(T_VARIABLE, $tokens[$catch]['parenthesis_closer'], $opener);

        if ($caught === false || $tokens[$caught]['content'] !== $tokens[$variable]['content']) {
            return [];
        }

        $types = explode('|', $phpcsFile->getTokensAsString($opener + 1, $caught - $opener - 1));

        return array_map($this->normalise(...), $types);
    }

    /**
     * Collect the types the @throws tags declare, splitting unions.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $opener
     * @return list<string>
     */
    private function declaredTypes(File $phpcsFile, int $opener): array
    {
        $tokens   = $phpcsFile->getTokens();
        $declared = [];

        foreach ($tokens[$opener]['comment_tags'] as $tag) {
            if ($tokens[$tag]['content'] !== '@throws' || $tokens[$tag + 2]['code'] !== T_DOC_COMMENT_STRING) {
                continue;
            }

            $type     = strtok((string) preg_replace('/\s*\|\s*/', '|', $tokens[$tag + 2]['content']), " \t");
            $declared = [...$declared, ...explode('|', (string) $type)];
        }

        return $declared;
    }

    /**
     * Determine whether the thrown type is declared, matching a qualified name
     * against an imported one by their shared suffix.
     *
     * @param  string  $type
     * @param  list<string>  $declared
     * @return bool
     */
    private function isDeclared(string $type, array $declared): bool
    {
        $type = strtolower($type);

        foreach (array_map(strtolower(...), $declared) as $candidate) {
            if ($candidate === $type || str_ends_with($candidate, '\\' . $type) || str_ends_with($type, '\\' . $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read the class name that starts at the pointer, or an empty string when
     * no name starts there.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $start
     * @return string
     */
    private function readName(File $phpcsFile, int $start): string
    {
        $end = $phpcsFile->findNext($this->nameTokenCodes(), $start, null, true);

        return $this->normalise($phpcsFile->getTokensAsString($start, (int) $end - $start));
    }

    /**
     * Strip surrounding space, a leading separator and a relative name's
     * `namespace` keyword from a class name.
     *
     * @param  string  $name
     * @return string
     */
    private function normalise(string $name): string
    {
        return ltrim((string) preg_replace('/^namespace\\\/i', '', trim($name)), '\\');
    }
}
