<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;

/**
 * Throws tag sniff.
 *
 * A documented function declares each exception its body throws in a tag of its
 * own. Unlike the core throw tag sniff, the number of tags is not held to the
 * number of exceptions thrown in the body: PHPStan asks for the checked
 * exceptions a callee propagates to be declared too, so a function that both
 * throws and propagates carries more tags than its body throws, and each type
 * thrown is still checked however many tags there are. An exception thrown
 * inside a closure or an anonymous class is not the function's own.
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
    private const array NESTED_SCOPES = [T_CLOSURE, T_ANON_CLASS];

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
     * The exception types the function at the pointer throws in its own body,
     * an empty list when it throws only what cannot be named, or null when it
     * throws nothing.
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
     * The exception types the throw at the pointer raises, read from the class
     * it instantiates or names, or from the catch whose variable it re-throws.
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
     * The types caught by the nearest catch before the re-thrown variable at
     * the pointer, when that catch binds the same variable.
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
     * The types the @throws tags of the doc comment at the pointer declare,
     * each member of a union counted on its own.
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
     * Whether the thrown type is among the declared ones, a name written
     * qualified on one side and imported on the other matching by the part the
     * two share.
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
     * A class name without surrounding space, a leading separator or the
     * namespace keyword of a relative name.
     *
     * @param  string  $name
     * @return string
     */
    private function normalise(string $name): string
    {
        return ltrim((string) preg_replace('/^namespace\\\/i', '', trim($name)), '\\');
    }
}
