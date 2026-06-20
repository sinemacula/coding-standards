<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Sensitive parameter attribute sniff.
 *
 * Requires #[\SensitiveParameter] on parameters whose name signals a secret
 * (password, token, apiKey, ...), so their values are redacted from stack
 * traces. Name matching is heuristic and configurable via $sensitiveNames.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireSensitiveParameterSniff implements Sniff
{
    /** @var array<int, string> Lower-case keywords that mark a parameter as sensitive. */
    public array $sensitiveNames = [
        'password',
        'passwd',
        'secret',
        'token',
        'apikey',
        'privatekey',
        'credential',
        'credentials',
    ];

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_FUNCTION, T_CLOSURE, T_FN];
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

        $lowerBound = $tokens[$stackPtr]['parenthesis_opener'];

        foreach ($phpcsFile->getMethodParameters($stackPtr) as $param) {
            $varPtr = $param['token'];

            if (
                $this->isSensitive($param['name'])
                && $this->isMarked($tokens, $lowerBound, $varPtr) === false
            ) {
                $phpcsFile->addError(
                    'Parameter "%s" looks sensitive and must be marked #[\SensitiveParameter].',
                    $varPtr,
                    'Missing',
                    [$param['name']],
                );
            }

            $lowerBound = $varPtr;
        }
    }

    /**
     * Determine whether a parameter name signals a secret.
     *
     * @param  string  $name
     * @return bool
     */
    private function isSensitive(string $name): bool
    {
        $name   = ltrim($name, '$');
        $joined = strtolower(str_replace('_', '', $name));
        $words  = array_map('strtolower', preg_split('/(?<=[a-z0-9])(?=[A-Z])|_+/', $name) ?: []);

        foreach ($this->sensitiveNames as $keyword) {
            if ($joined === $keyword || in_array($keyword, $words, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether a #[\SensitiveParameter] attribute precedes the param.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $from
     * @param  int  $to
     * @return bool
     */
    private function isMarked(array $tokens, int $from, int $to): bool
    {
        for ($i = $from + 1; $i < $to; $i++) {
            if ($tokens[$i]['code'] === T_STRING && $tokens[$i]['content'] === \SensitiveParameter::class) {
                return true;
            }
        }

        return false;
    }
}
