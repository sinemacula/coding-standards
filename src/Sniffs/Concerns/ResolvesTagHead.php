<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

use PHP_CodeSniffer\Files\File;

/**
 * Resolves a doc comment tag's type and what follows it, across however many
 * lines the type spans.
 *
 * Bracket contents are skipped, so a callable type's own parameter is not taken
 * for the tag's variable; brackets inside quoted literals open nothing.
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

    /** @var string A quoted string literal, closed on the line it opens on. */
    private const string LITERAL = '/\G(?:\'(?:[^\'\\\\\n]|\\\.)*\'|"(?:[^"\\\\\n]|\\\.)*")/';

    /** @var string A line that carries on the union or intersection of the line before. */
    private const string CONTINUATION = '/\G[ \t]*(?:\||&(?![.]*\$))/';

    /**
     * The text tokens between the tag and the bound.
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
     * The offset and text of the first pattern match outside every bracket and
     * literal, or null when none.
     *
     * A line break outside brackets ends the run unless the next line continues
     * a union or intersection.
     *
     * @param  list<string>  $lines
     * @param  string  $pattern
     * @return array{int, string}|null
     */
    protected function matchOutsideBrackets(array $lines, string $pattern): ?array
    {
        $text   = implode("\n", $lines);
        $length = strlen($text);
        $depth  = 0;
        $offset = $this->literalEnd($text, 0);

        while ($offset < $length) {
            $depth += self::BRACKETS[$text[$offset]] ?? 0;

            if ($depth <= 0 && preg_match($pattern, $text, $match, 0, $offset) === 1) {
                return [$offset, $match[0]];
            }

            if ($depth <= 0 && $this->endsTheRun($text, $offset)) {
                return null;
            }

            $offset = $this->literalEnd($text, $offset + 1);
        }

        return null;
    }

    /**
     * Whether the offset is a line break not followed by a union or
     * intersection continuation.
     *
     * @param  string  $text
     * @param  int  $offset
     * @return bool
     */
    private function endsTheRun(string $text, int $offset): bool
    {
        return $text[$offset] === "\n" && preg_match(self::CONTINUATION, $text, $match, 0, $offset + 1) !== 1;
    }

    /**
     * The offset where a quoted literal opening at the offset closes, or the
     * offset itself when none does.
     *
     * @param  string  $text
     * @param  int  $offset
     * @return int
     */
    private function literalEnd(string $text, int $offset): int
    {
        if (preg_match(self::LITERAL, $text, $match, 0, $offset) !== 1) {
            return $offset;
        }

        return $offset + strlen($match[0]) - 1;
    }
}
