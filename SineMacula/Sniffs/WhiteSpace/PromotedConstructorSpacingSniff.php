<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Promoted constructor parameter spacing sniff.
 *
 * Requires a blank line above each parameter of a constructor that promotes
 * properties: one after the opening parenthesis and one between parameters.
 * This pads the one-per-line promoted constructor that cs-fixer produces via
 * multiline_promoted_properties. The closing parenthesis stays tight against
 * the last parameter (cs-fixer strips a blank there), so none is required
 * before it.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class PromotedConstructorSpacingSniff implements Sniff
{
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

        if (isset($tokens[$stackPtr]['parenthesis_opener']) === false) {
            return; // @codeCoverageIgnore
        }

        $opener = $tokens[$stackPtr]['parenthesis_opener'];
        $closer = $tokens[$stackPtr]['parenthesis_closer'];
        $params = $phpcsFile->getMethodParameters($stackPtr);

        if ($this->hasPromotedProperty($params) === false || $tokens[$opener]['line'] === $tokens[$closer]['line']) {
            return;
        }

        $this->checkParameterSpacing($phpcsFile, $opener, $closer, $params);
    }

    /**
     * Flag each constructor parameter not preceded by a blank line.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $opener
     * @param  int  $closer
     * @param  array<int, array<string, mixed>>  $params
     * @return void
     */
    private function checkParameterSpacing(File $phpcsFile, int $opener, int $closer, array $params): void
    {
        $tokens   = $phpcsFile->getTokens();
        $previous = $opener;

        foreach ($params as $param) {
            $blockStart = $phpcsFile->findNext(T_WHITESPACE, $previous + 1, $closer, true);

            if ($blockStart !== false && ($tokens[$blockStart]['line'] - $tokens[$previous]['line']) < 2) {
                $phpcsFile->addError(
                    'Constructor parameter "%s" must be preceded by a blank line.',
                    $blockStart,
                    'NoBlankLine',
                    [$param['name']],
                );
            }

            $previous = $param['comma_token'] === false ? $param['token'] : $param['comma_token'];
        }
    }

    /**
     * Determine whether any parameter is a promoted property.
     *
     * @param  array<int, array<string, mixed>>  $params
     * @return bool
     */
    private function hasPromotedProperty(array $params): bool
    {
        foreach ($params as $param) {
            if (isset($param['property_visibility'])) {
                return true;
            }
        }

        return false;
    }
}
