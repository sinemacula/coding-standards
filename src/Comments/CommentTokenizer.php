<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Comments;

/**
 * Splits comment prose into wrapping tokens.
 *
 * A token is a run of non-space characters, except that a handful of constructs
 * may hold spaces yet must never be split across a line break: inline backtick
 * code, an inline `{@link ...}` tag and a Markdown `[text](url)` link. Each is
 * consumed whole. A delimiter that never closes is treated as an ordinary
 * character, so malformed prose still tokenises predictably.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class CommentTokenizer
{
    /**
     * Split a line of prose into tokens, keeping atomic spans intact.
     *
     * @param  string  $text
     * @return list<string>
     */
    public function tokenize(string $text): array
    {
        $chars  = mb_str_split($text);
        $count  = count($chars);
        $tokens = [];
        $i      = 0;

        while ($i < $count) {
            if ($chars[$i] === ' ') {
                $i++;

                continue;
            }

            $start = $i;
            $i     = $this->consumeToken($chars, $count, $i);

            $tokens[] = implode('', array_slice($chars, $start, $i - $start));
        }

        return $tokens;
    }

    /**
     * Advance past a single token, stepping over any atomic span it contains.
     *
     * @param  list<string>  $chars
     * @param  int  $count
     * @param  int  $i
     * @return int
     */
    private function consumeToken(array $chars, int $count, int $i): int
    {
        while ($i < $count && $chars[$i] !== ' ') {
            $i = match (true) {
                $chars[$i] === '`'                                   => $this->consumeSpan($chars, $count, $i, '`'),
                $chars[$i] === '{' && ($chars[$i + 1] ?? '') === '@' => $this->consumeSpan($chars, $count, $i, '}'),
                $chars[$i] === '['                                   => $this->consumeLink($chars, $count, $i),
                default                                              => $i + 1,
            };
        }

        return $i;
    }

    /**
     * Advance past a delimited span to its closer, or by one when it never
     * closes.
     *
     * @param  list<string>  $chars
     * @param  int  $count
     * @param  int  $i
     * @param  string  $close
     * @return int
     */
    private function consumeSpan(array $chars, int $count, int $i, string $close): int
    {
        for ($j = $i + 1; $j < $count; $j++) {
            if ($chars[$j] === $close) {
                return $j + 1;
            }
        }

        return $i + 1;
    }

    /**
     * Advance past a Markdown link `[text](url)`, or by one when the shape does
     * not hold.
     *
     * @param  list<string>  $chars
     * @param  int  $count
     * @param  int  $i
     * @return int
     */
    private function consumeLink(array $chars, int $count, int $i): int
    {
        $label = $this->consumeSpan($chars, $count, $i, ']');

        if ($label === $i + 1 || ($chars[$label] ?? '') !== '(') {
            return $i + 1;
        }

        $target = $this->consumeSpan($chars, $count, $label, ')');

        return $target === $label + 1 ? $i + 1 : $target;
    }
}
