<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;

/**
 * Base sniff requiring a declaration to live under a namespace segment.
 *
 * A subclass declares only what distinguishes it: the token to inspect, the
 * subject noun and the error code as constants, and the required segment as the
 * default of an inherited property a ruleset can override. This base does the
 * rest, flagging any declaration whose namespace lacks that segment at any
 * depth. Holding those four as data rather than as overridden methods is what
 * keeps the subclasses from being three copies of one shape; that they are each
 * filled in is covered by a test, since a constant cannot be declared abstract.
 * It lives under the Composer-autoloaded namespace (not the
 * PHP_CodeSniffer-scanned Sniffs tree) so it is never registered as a sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class AbstractRequiredNamespaceSniff implements Sniff
{
    use ResolvesQualifiedNames;

    /** @var array<int, int|string> The tokens the sniff listens for; a subclass names its own. */
    protected const array TOKENS = [];

    /** @var string The subject noun used in the message (e.g. "Interface", "Trait"). */
    protected const string SUBJECT = '';

    /** @var string The sniff error code, which stays put when the segment is reconfigured. */
    protected const string ERROR_CODE = '';

    /** @var string The namespace segment the declaration must live under. */
    public string $segment = '';

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return static::TOKENS;
    }

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

        if (in_array($this->segment, $segments, true)) {
            return;
        }

        $phpcsFile->addError(
            '%s "%s" must be declared in a "%s" namespace.',
            $stackPtr,
            static::ERROR_CODE,
            [static::SUBJECT, $phpcsFile->getDeclarationName($stackPtr), $this->segment],
        );
    }

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
            if (!in_array($tokens[$i]['code'], $this->nameTokenCodes(), true)) {
                continue;
            }

            $name .= $tokens[$i]['content'];
        }

        return trim($name, '\\');
    }
}
