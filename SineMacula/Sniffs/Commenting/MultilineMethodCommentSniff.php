<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;

/**
 * Multi-line method comment sniff.
 *
 * A method earns a fuller account than a data field: what it does, and its
 * parameters and what it returns. Holding its doc comment open across lines
 * keeps that room ready and sets behaviour apart from data at a glance, the
 * mirror of the single-line form data members take. Methods declared in a
 * class, interface, trait or enum are governed; a free function is left to the
 * author, since a short helper reads well on one line.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class MultilineMethodCommentSniff implements Sniff
{
    use ResolvesDocComment;

    /** @var array<int, int|string> Class-like scopes a method is declared within. */
    private const array CLASS_SCOPES = [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_ANON_CLASS];

    /** @var array<int, int|string> Method modifiers a doc comment sits above. */
    private const array METHOD_MODIFIERS = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT];

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
        if (!$this->isMethod($phpcsFile, $stackPtr)) {
            return;
        }

        $closer = $this->docCommentCloser($phpcsFile, $stackPtr, self::METHOD_MODIFIERS);

        if ($closer === null) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$closer]['comment_opener'];

        if ($tokens[$opener]['line'] !== $tokens[$closer]['line']) {
            return;
        }

        $phpcsFile->addError('A method doc comment must span multiple lines.', $opener, 'SingleLine');
    }

    /**
     * Whether the function at the pointer is a method: one declared directly in
     * a class, interface, trait or enum rather than a free or nested function.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isMethod(File $phpcsFile, int $stackPtr): bool
    {
        $conditions = $phpcsFile->getTokens()[$stackPtr]['conditions'];

        return in_array(end($conditions), self::CLASS_SCOPES, true);
    }
}
