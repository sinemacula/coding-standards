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
 * documents. A bracket inside a quoted literal type, such as '(', opens
 * nothing, and a type closed at the end of a line goes on to the next only when
 * that line carries on its union or intersection.
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
     * The first match of the pattern that starts outside every bracket and
     * quoted literal, read across the lines as one run with a line break
     * standing between each, as the offset it starts at and the text it
     * matched, or null when there is none. A line break outside every bracket
     * ends the run unless the next line carries on a union or intersection.
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
     * Whether the offset is a line break the next line does not carry a union
     * or intersection over.
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
     * Where the quoted literal that opens at the offset closes, or the offset
     * itself when no literal closes on its line there.
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
