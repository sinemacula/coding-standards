<?php

namespace SineMacula\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Contracts namespace sniff.
 *
 * Requires every interface to be declared under a `Contracts` namespace segment
 * (at any depth, e.g. `App\Contracts` or `App\Billing\Contracts`), keeping
 * ports and contracts grouped and discoverable.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireContractsNamespaceSniff implements Sniff
{
    /** The namespace segment that interfaces must live under. */
    public string $contractsSegment = 'Contracts';

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_INTERFACE];
    }

    /**
     * Process an interface declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $segments = explode('\\', $this->namespace($phpcsFile, $stackPtr));

        if (in_array($this->contractsSegment, $segments, true)) {
            return;
        }

        $phpcsFile->addError(
            'Interface "%s" must be declared in a "%s" namespace.',
            $stackPtr,
            'NotInContracts',
            [$phpcsFile->getDeclarationName($stackPtr), $this->contractsSegment]
        );
    }

    /**
     * Resolve the namespace the interface is declared in.
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
            if (in_array($tokens[$i]['code'], [T_STRING, T_NS_SEPARATOR], true)) {
                $name .= $tokens[$i]['content'];
            }
        }

        return trim($name, '\\');
    }
}
