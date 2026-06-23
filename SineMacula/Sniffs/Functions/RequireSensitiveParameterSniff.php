<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;

/**
 * Sensitive parameter attribute sniff.
 *
 * Requires #[\SensitiveParameter] on parameters whose name signals a secret
 * (password, secret, access_token, ...), so their values are redacted from
 * stack traces. Bare "token" is intentionally excluded - it matches operator
 * and CSRF tokens that are not secrets. The list is a public $sensitiveNames
 * array a ruleset can extend or override. Test classes are exempt, where
 * sensitive-looking names exercise redaction rather than handle real secrets.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireSensitiveParameterSniff implements Sniff
{
    use DetectsTestClasses;
    use ResolvesQualifiedNames;

    /** @var array<int, string> Lower-case keywords that mark a parameter as sensitive. */
    public array $sensitiveNames = [
        'password',
        'passwd',
        'secret',
        'passphrase',
        'apikey',
        'privatekey',
        'credential',
        'credentials',
        'accesstoken',
        'authtoken',
        'refreshtoken',
        'bearertoken',
        'idtoken',
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

        if ($this->isInTestClass($phpcsFile, $stackPtr)) {
            return;
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
     * Whether the function is declared in a test class, where sensitive-looking
     * parameter names exercise redaction rather than handle real secrets.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return bool
     */
    private function isInTestClass(File $phpcsFile, int $stackPtr): bool
    {
        $classPtr = $phpcsFile->getCondition($stackPtr, T_CLASS, false);

        return $classPtr !== false && $this->isTestClass($phpcsFile, $classPtr);
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
        $names = [\SensitiveParameter::class, '\\' . \SensitiveParameter::class];

        for ($i = $from + 1; $i < $to; $i++) {
            if (
                in_array($tokens[$i]['code'], $this->nameTokenCodes(), true)
                && in_array($tokens[$i]['content'], $names, true)
            ) {
                return true;
            }
        }

        return false;
    }
}
