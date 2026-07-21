<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;

/**
 * Single-line data member comment sniff.
 *
 * A data member's description is a single phrase, so where it carries a doc
 * comment that comment sits on one line. Properties, class constants and enum
 * cases are governed; a method reads as behaviour and keeps a multi-line block.
 * No comment is ever required here, only held to one line where it is present,
 * so enum cases stay documented at the author's discretion.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class SingleLineMemberCommentSniff implements Sniff
{
    use ResolvesDocComment;

    /** @var array<int, int|string> Class-like scopes a property is declared within. */
    private const array CLASS_SCOPES = [T_CLASS, T_TRAIT, T_ENUM, T_ANON_CLASS];

    /** @var array<int, int|string> Constant modifiers a doc comment sits above. */
    private const array CONSTANT_MODIFIERS = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL];

    /** @var array<int, int|string> Property modifiers and type tokens a doc comment sits above. */
    private const array PROPERTY_MODIFIERS = [
        T_PUBLIC, T_PROTECTED, T_PRIVATE,
        T_VAR, T_STATIC, T_READONLY, T_FINAL,
        T_STRING, T_NS_SEPARATOR, T_NAMESPACE, T_NULLABLE,
        T_TYPE_UNION, T_TYPE_INTERSECTION,
        T_TYPE_OPEN_PARENTHESIS, T_TYPE_CLOSE_PARENTHESIS,
        T_NULL, T_TRUE, T_FALSE, T_SELF, T_PARENT,
    ];

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_VARIABLE, T_CONST, T_ENUM_CASE];
    }

    /**
     * Process a data member declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $closer = $this->memberDocCloser($phpcsFile, $stackPtr);

        if ($closer === null) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$closer]['comment_opener'];

        if ($tokens[$opener]['line'] === $tokens[$closer]['line']) {
            return;
        }

        $phpcsFile->addError('A data member doc comment must be on a single line.', $opener, 'Multiline');
    }

    /**
     * Resolve the doc comment closer for the data member at the pointer, or
     * null when the token is not a documented data member.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return int|null
     */
    private function memberDocCloser(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();

        return match ($tokens[$stackPtr]['code']) {
            T_CONST => empty($tokens[$stackPtr]['conditions'])
                ? null
                : $this->docCommentCloser($phpcsFile, $stackPtr, self::CONSTANT_MODIFIERS),
            T_ENUM_CASE => $this->docCommentCloser($phpcsFile, $stackPtr),
            default     => $this->isMemberProperty($phpcsFile, $stackPtr)
                ? $this->docCommentCloser($phpcsFile, $stackPtr, self::PROPERTY_MODIFIERS)
                : null,
        };
    }

    /**
     * Whether the variable at the pointer declares a class member property,
     * distinguishing a declaration in a class body from a promoted parameter, a
     * local variable or a static access inside a method.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isMemberProperty(File $phpcsFile, int $stackPtr): bool
    {
        $conditions = $phpcsFile->getTokens()[$stackPtr]['conditions'];

        return in_array(end($conditions), self::CLASS_SCOPES, true);
    }
}
