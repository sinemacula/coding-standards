<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

use PHP_CodeSniffer\Files\File;

/**
 * Resolves the head of a doc comment tag, its type and what follows the type,
 * however many lines the type spans.
 *
 * A type may open a bracket on the tag's own line and close it several lines
 * further down, as an array shape or a callable signature written one member to
 * a line does. Reading the tag's lines as one run, and skipping whatever sits
 * inside a bracket, follows such a type to its close, so the variable a
 * callable type names for its own parameter is not taken for the one the tag
 * documents.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
trait ResolvesTagHead
{
    /** @var array<string, int> How far each bracket moves the nesting depth of a type. */
    private const array BRACKETS = [
        '<' => 1,
        '(' => 1,
        '{' => 1,
        '[' => 1,
        '>' => -1,
        ')' => -1,
        '}' => -1,
        ']' => -1,
    ];

    /**
     * The text tokens that belong to the tag at the pointer, from the tag up to
     * the next tag or the end of the comment.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  int  $tag
     * @param  int  $bound
     * @return list<int>
     */
    protected function tagStrings(array $tokens, int $tag, int $bound): array
    {
        return array_values(array_filter(
            range($tag, $bound),
            static fn (int $ptr): bool => $tokens[$ptr]['code'] === T_DOC_COMMENT_STRING,
        ));
    }

    /**
     * The text each of the given tokens carries, one line to a token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  list<int>  $strings
     * @return list<string>
     */
    protected function tagLines(File $phpcsFile, array $strings): array
    {
        return array_map(static fn (int $ptr): string => $phpcsFile->getTokensAsString($ptr, 1), $strings);
    }

    /**
     * The first match of the pattern that starts outside every bracket, read
     * across the lines as one run with a line break standing between each, as
     * the offset it starts at and the text it matched, or null when there is
     * none.
     *
     * @param  list<string>  $lines
     * @param  string  $pattern
     * @return array{int, string}|null
     */
    protected function matchOutsideBrackets(array $lines, string $pattern): ?array
    {
        $text  = implode("\n", $lines);
        $depth = 0;

        for ($offset = 0, $length = strlen($text); $offset < $length; $offset++) {
            $depth += self::BRACKETS[$text[$offset]] ?? 0;

            if ($depth <= 0 && preg_match($pattern, $text, $match, 0, $offset) === 1) {
                return [$offset, $match[0]];
            }
        }

        return null;
    }
}
