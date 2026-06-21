<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Concerns namespace sniff.
 *
 * Requires every trait to be declared under a `Concerns` namespace segment (at
 * any depth, e.g. `App\Concerns` or `App\Billing\Concerns`), keeping reusable
 * behaviour grouped and discoverable, mirroring the Contracts rule for
 * interfaces.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireConcernsNamespaceSniff implements Sniff
{
    /** @var string The namespace segment that traits must live under. */
    public string $concernsSegment = 'Concerns';

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_TRAIT];
    }

    /**
     * Process a trait declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $segments = explode('\\', $this->namespace($phpcsFile, $stackPtr));

        if (in_array($this->concernsSegment, $segments, true)) {
            return;
        }

        $phpcsFile->addError(
            'Trait "%s" must be declared in a "%s" namespace.',
            $stackPtr,
            'NotInConcerns',
            [$phpcsFile->getDeclarationName($stackPtr), $this->concernsSegment],
        );
    }

    /**
     * Resolve the namespace the trait is declared in.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return string
     */
    private function namespace(File $phpcsFile, int $stackPtr): string
    {
        $tokens = $phpcsFile->getTokens();
        $nsPtr  = $phpcsFile->findPrevious(T_NAMESPACE, $stackPtr - 1);

        if ($nsPtr === false) {
            return '';
        }

        $end  = $phpcsFile->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], $nsPtr + 1);
        $name = '';

        for ($i = $nsPtr + 1; $i < $end; $i++) {
            if (!in_array($tokens[$i]['code'], [T_STRING, T_NS_SEPARATOR], true)) {
                continue;
            }

            $name .= $tokens[$i]['content'];
        }

        return trim($name, '\\');
    }
}
