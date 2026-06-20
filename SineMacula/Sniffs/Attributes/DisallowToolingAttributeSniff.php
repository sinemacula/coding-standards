<?php

namespace SineMacula\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Disallow IDE / tooling attributes.
 *
 * PHP attributes should describe runtime behaviour, not feed IDE or static
 * analysis hints. This sniff forbids attributes declared under known tooling
 * namespaces (JetBrains\PhpStorm by default); native PHP attributes and runtime
 * framework attributes are unaffected. Express the same intent with real types
 * and docblocks instead.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class DisallowToolingAttributeSniff implements Sniff
{
    /** @var array<int, string> Namespaces whose attributes are forbidden. */
    public array $forbiddenNamespaces = ['JetBrains\\PhpStorm'];

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return [T_ATTRIBUTE];
    }

    /**
     * Process an attribute group token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['attribute_closer'];
        $uses   = $this->useMap($phpcsFile);

        foreach ($this->attributeNames($phpcsFile, $stackPtr, $closer) as $namePtr => $name) {
            $resolved = $this->resolve($name, $uses);

            foreach ($this->forbiddenNamespaces as $namespace) {
                if (stripos($resolved . '\\', trim($namespace, '\\') . '\\') === 0) {
                    $phpcsFile->addError(
                        'Attribute "%s" is an IDE/tooling attribute and is not allowed; use native types and docblocks instead.',
                        $namePtr,
                        'Disallowed',
                        [$name]
                    );

                    break;
                }
            }
        }
    }

    /**
     * Collect each attribute name (and its first token) within an attribute group.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $opener
     * @param  int  $closer
     * @return array<int, string>
     */
    private function attributeNames(File $phpcsFile, int $opener, int $closer): array
    {
        $tokens = $phpcsFile->getTokens();
        $names  = [];
        $i      = $opener + 1;

        while ($i < $closer) {
            if (in_array($tokens[$i]['code'], [T_STRING, T_NS_SEPARATOR], true) === false) {
                $i++;

                continue;
            }

            $start = $i;
            $name  = '';

            while ($i < $closer && in_array($tokens[$i]['code'], [T_STRING, T_NS_SEPARATOR], true)) {
                $name .= $tokens[$i]['content'];
                $i++;
            }

            $names[$start] = $name;

            // Skip any argument list so its contents are not mistaken for names.
            if ($i < $closer && $tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                $i = $tokens[$i]['parenthesis_closer'] + 1;
            }
        }

        return $names;
    }

    /**
     * Build a map of imported alias to fully qualified name for the file.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @return array<string, string>
     */
    private function useMap(File $phpcsFile): array
    {
        $tokens = $phpcsFile->getTokens();
        $map    = [];

        foreach ($tokens as $ptr => $token) {
            if ($token['code'] !== T_USE || empty($token['conditions']) === false) {
                continue;
            }

            $import = $this->parseImport($phpcsFile, $ptr);

            if ($import !== null) {
                $map[$import[0]] = $import[1];
            }
        }

        return $map;
    }

    /**
     * Parse a single `use` statement into [alias, fully qualified name].
     *
     * Returns null when the statement is not a class import (closure use, or a
     * function/const import) or carries no name.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $usePtr
     * @return array{0: string, 1: string}|null
     */
    private function parseImport(File $phpcsFile, int $usePtr): ?array
    {
        $tokens = $phpcsFile->getTokens();
        $next   = $phpcsFile->findNext(T_WHITESPACE, $usePtr + 1, null, true);
        $end    = $phpcsFile->findNext(T_SEMICOLON, $usePtr + 1);

        // Ignore malformed statements and closure `use (...)`, `use function`, `use const`.
        if ($end === false || in_array($tokens[$next]['code'], [T_OPEN_PARENTHESIS, T_FUNCTION, T_CONST], true)) {
            return null; // @codeCoverageIgnore
        }

        $name  = '';
        $alias = null;

        for ($i = $next; $i < $end; $i++) {
            if (in_array($tokens[$i]['code'], [T_STRING, T_NS_SEPARATOR], true)) {
                $name .= $tokens[$i]['content'];
            } elseif ($tokens[$i]['code'] === T_AS) {
                $aliasPtr = $phpcsFile->findNext(T_STRING, $i + 1, $end);
                $alias    = $aliasPtr === false ? null : $tokens[$aliasPtr]['content'];

                break;
            }
        }

        $name = ltrim($name, '\\');

        if ($name === '') {
            return null; // @codeCoverageIgnore
        }

        if ($alias === null) {
            $segments = explode('\\', $name);
            $alias    = end($segments);
        }

        return [$alias, $name];
    }

    /**
     * Resolve an attribute name to its fully qualified name using the use map.
     *
     * @param  string  $name
     * @param  array<string, string>  $uses
     * @return string
     */
    private function resolve(string $name, array $uses): string
    {
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }

        $segments = explode('\\', $name);
        $first    = $segments[0];

        if (isset($uses[$first]) === false) {
            return $name;
        }

        $rest = count($segments) > 1 ? '\\' . implode('\\', array_slice($segments, 1)) : '';

        return $uses[$first] . $rest;
    }
}
