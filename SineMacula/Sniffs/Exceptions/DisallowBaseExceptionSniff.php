<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Exceptions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;

/**
 * Base exception throw sniff.
 *
 * Forbids throwing a base engine exception directly (`throw new
 * \Exception(...)`) so a domain or typed exception is used instead. The
 * forbidden class names are configurable via $forbiddenExceptions. Re-throws
 * of a variable are not checked.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class DisallowBaseExceptionSniff implements Sniff
{
    use ResolvesQualifiedNames;

    /** @var array<int, string> Base exception names that may not be thrown directly. */
    public array $forbiddenExceptions = [\Exception::class];

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_THROW];
    }

    /**
     * Process a throw statement token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $end    = $phpcsFile->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], $stackPtr + 1);
        $newPtr = $phpcsFile->findNext(T_NEW, $stackPtr + 1, $end === false ? null : $end);

        if ($newPtr === false) {
            return;
        }

        $name       = $this->className($phpcsFile, $newPtr);
        $normalised = ltrim($name, '\\');

        foreach ($this->forbiddenExceptions as $class) {
            if (strcasecmp($normalised, ltrim($class, '\\')) === 0) {
                $phpcsFile->addError(
                    'Throw a domain exception, not the base "%s".',
                    $newPtr,
                    'BaseException',
                    [$name],
                );

                return;
            }
        }
    }

    /**
     * Read the class name that immediately follows a `new` keyword.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $newPtr
     * @return string
     */
    private function className(File $phpcsFile, int $newPtr): string
    {
        $tokens = $phpcsFile->getTokens();
        $start  = $phpcsFile->findNext(T_WHITESPACE, $newPtr + 1, null, true);

        if ($start === false) {
            return ''; // @codeCoverageIgnore
        }

        $name = '';

        for ($i = $start; isset($tokens[$i]) && in_array($tokens[$i]['code'], $this->nameTokenCodes(), true); $i++) {
            $name .= $tokens[$i]['content'];
        }

        return $name;
    }
}
