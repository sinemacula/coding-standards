<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Base sniff requiring a declaration to live under a namespace segment.
 *
 * Subclasses register the token to inspect (interface, trait) and supply the
 * required segment, the subject noun and the error code; this base flags any
 * declaration whose namespace lacks that segment at any depth. It lives under
 * the Composer-autoloaded namespace (not the PHP_CodeSniffer-scanned Sniffs
 * tree) so it is never registered as a sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class AbstractRequiredNamespaceSniff implements Sniff
{
    /**
     * Process a declaration token, flagging it when the segment is absent.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $segments = explode('\\', $this->namespace($phpcsFile, $stackPtr));

        if (in_array($this->segment(), $segments, true)) {
            return;
        }

        $phpcsFile->addError(
            '%s "%s" must be declared in a "%s" namespace.',
            $stackPtr,
            $this->errorCode(),
            [$this->subject(), $phpcsFile->getDeclarationName($stackPtr), $this->segment()],
        );
    }

    /**
     * The namespace segment the declaration must live under.
     *
     * @return string
     */
    abstract protected function segment(): string;

    /**
     * The subject noun used in the message (e.g. "Interface", "Trait").
     *
     * @return string
     */
    abstract protected function subject(): string;

    /**
     * The sniff error code.
     *
     * @return string
     */
    abstract protected function errorCode(): string;

    /**
     * Resolve the namespace the declaration is in.
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
