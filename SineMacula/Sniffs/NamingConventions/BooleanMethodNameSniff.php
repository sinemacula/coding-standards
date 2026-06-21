<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Boolean method name sniff.
 *
 * A method (or function) that returns `bool` should read as a predicate, i.e.
 * begin with one of the configured prefixes - a predicate verb (is/has/can/...)
 * or a conventional handler name (handle). The list is configurable via
 * $allowedPrefixes; magic methods are exempt.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class BooleanMethodNameSniff implements Sniff
{
    /** @var array<int, string> Prefixes a boolean method name may begin with. */
    public array $allowedPrefixes = [
        'is', 'are', 'was', 'were', 'has', 'have', 'had', 'can', 'could',
        'should', 'shall', 'will', 'would', 'must', 'may', 'might', 'do',
        'does', 'did', 'contains', 'supports', 'allows', 'accepts', 'matches',
        'equals', 'includes', 'requires', 'exists', 'needs', 'wants', 'knows',
        'owns', 'uses', 'expects', 'starts', 'ends', 'begins', 'lacks', 'handle',
    ];

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
        $properties = $phpcsFile->getMethodProperties($stackPtr);

        if (ltrim((string) $properties['return_type'], '?') !== 'bool') {
            return;
        }

        $name = $phpcsFile->getDeclarationName($stackPtr);

        if ($name === null || str_starts_with($name, '__') || $this->isPredicate($name)) {
            return;
        }

        $phpcsFile->addError(
            'Boolean method "%s" should read as a predicate (is/has/can/...).',
            $stackPtr,
            'NotPredicate',
            [$name],
        );
    }

    /**
     * Determine whether a name begins with an allowed predicate verb.
     *
     * @param  string  $name
     * @return bool
     */
    private function isPredicate(string $name): bool
    {
        $verbs   = implode('|', array_map('preg_quote', $this->allowedPrefixes));
        $pattern = '/^(' . $verbs . ')([A-Z0-9]|$)/';

        return preg_match($pattern, $name) === 1;
    }
}
