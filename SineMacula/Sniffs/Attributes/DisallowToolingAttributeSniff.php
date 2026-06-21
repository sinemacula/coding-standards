<?php

declare(strict_types = 1);

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
    public array $forbiddenNamespaces = ['JetBrains\PhpStorm'];

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
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
    #[\Override]
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
                        'Attribute "%s" is an IDE/tooling attribute and is not allowed; '
                        . 'use native types and docblocks instead.',
                        $namePtr,
                        'Disallowed',
                        [$name],
                    );

                    break;
                }
            }
        }
    }

    /**
     * Collect each attribute name and its first token in an attribute group.
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
        $parts  = [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED];

        while ($i < $closer) {
            if (in_array($tokens[$i]['code'], $parts, true) === false) {
                $i++;

                continue;
            }

            $start         = $i;
            $names[$start] = $this->readName($tokens, $i, $closer);

            // Skip any argument list so its tokens are not mistaken for names.
            if ($i >= $closer || $tokens[$i]['code'] !== T_OPEN_PARENTHESIS) {
                continue;
            }

            $i = $tokens[$i]['parenthesis_closer'] + 1;
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

            if ($import === null) {
                continue; // @codeCoverageIgnore
            }

            $map[$import[0]] = $import[1];
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

        // Skip malformed statements, function/const imports and closure binds.
        if ($end === false || in_array($tokens[$next]['code'], [T_OPEN_PARENTHESIS, T_FUNCTION, T_CONST], true)) {
            return null; // @codeCoverageIgnore
        }

        $i    = $next;
        $name = ltrim($this->readName($tokens, $i, $end), '\\');

        if ($name === '') {
            return null; // @codeCoverageIgnore
        }

        $asPtr    = $phpcsFile->findNext(T_AS, $i, $end);
        $aliasPtr = $asPtr    === false ? false : $phpcsFile->findNext(T_STRING, $asPtr + 1, $end);
        $alias    = $aliasPtr === false ? null : $tokens[$aliasPtr]['content'];

        if ($alias === null) {
            $segments = explode('\\', $name);
            $alias    = end($segments);
        }

        return [$alias, $name];
    }

    /**
     * Read a qualified name from consecutive name tokens, advancing $i.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $i
     * @param  int  $end
     * @return string
     */
    private function readName(array $tokens, int &$i, int $end): string
    {
        $name  = '';
        $parts = [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED];

        while ($i < $end && in_array($tokens[$i]['code'], $parts, true)) {
            $name .= $tokens[$i]['content'];
            $i++;
        }

        return $name;
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
