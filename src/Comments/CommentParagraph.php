<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Comments;

/**
 * Reflows a single paragraph of comment prose to its greedy canonical form.
 *
 * A paragraph is a run of prose lines, or one list item with its continuation
 * lines. It is filled line by line with as many whole words as the width
 * allows, never splitting a word and keeping any list marker's hanging indent.
 * A paragraph is reflowed only when it is safe to: it holds no token wider than
 * the line, is not too narrow, and would still read as prose once wrapped.
 * Otherwise it is returned untouched.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class CommentParagraph
{
    /** @var \SineMacula\CodingStandards\Comments\CommentTokenizer The prose tokenizer. */
    private CommentTokenizer $tokenizer;

    /** @var \SineMacula\CodingStandards\Comments\CommentLineClassifier The line classifier. */
    private CommentLineClassifier $classifier;

    /**
     * @param  int  $maxLength
     * @return void
     */
    public function __construct(

        /** @var int The maximum width a comment line may fill. */
        private int $maxLength,
    ) {
        $this->tokenizer  = new CommentTokenizer;
        $this->classifier = new CommentLineClassifier;
    }

    /**
     * Reflow a paragraph, returning its output lines and the faults found,
     * their indices offset from the given base, or the verbatim input when it
     * cannot be reflowed or carries no fault.
     *
     * @param  list<string>  $slice
     * @param  array{marker: string, indent: int, width: int}|null  $marker
     * @param  int  $marginWidth
     * @param  int  $base
     * @return array{lines: list<string>, long: list<int>, premature: list<int>}
     */
    public function reflow(array $slice, ?array $marker, int $marginWidth, int $base): array
    {
        $wrapped = $this->wrapLines($slice, $marker, $marginWidth);
        $faults  = $wrapped === null ? ['long' => [], 'premature' => []] : $this->faults($slice, $marginWidth);

        if ($wrapped === null || ($faults['long'] === [] && $faults['premature'] === [])) {
            return ['lines' => $slice, 'long' => [], 'premature' => []];
        }

        return [
            'lines'     => $wrapped,
            'long'      => array_map(static fn (int $offset): int => $base + $offset, $faults['long']),
            'premature' => array_map(static fn (int $offset): int => $base + $offset, $faults['premature']),
        ];
    }

    /**
     * The greedy canonical lines of a paragraph, or null when it holds an
     * unwrappable token, is too narrow, or would re-classify once wrapped.
     *
     * @param  list<string>  $slice
     * @param  array{marker: string, indent: int, width: int}|null  $marker
     * @param  int  $marginWidth
     * @return list<string>|null
     */
    private function wrapLines(array $slice, ?array $marker, int $marginWidth): ?array
    {
        $indent  = $marker === null ? $this->leadingSpaces($slice[0]) : $marker['indent'];
        $hanging = $indent + ($marker === null ? 0 : $marker['width']);
        $width   = $this->maxLength - $marginWidth - $hanging;
        $tokens  = $this->glue($this->tokenizer->tokenize($this->joinText($slice, $marker)));

        if ($width < 1 || $tokens === [] || $this->longestToken($tokens) > $width) {
            return null;
        }

        $lines = $this->layout($this->greedy($tokens, $width), $marker, $indent, $hanging);

        return $this->isStable($lines, $marker) ? $lines : null;
    }

    /**
     * Assemble output lines from wrapped text, prefixing the marker on the
     * first line and the hanging indent on the rest.
     *
     * @param  list<string>  $wrapped
     * @param  array{marker: string, indent: int, width: int}|null  $marker
     * @param  int  $indent
     * @param  int  $hanging
     * @return list<string>
     */
    private function layout(array $wrapped, ?array $marker, int $indent, int $hanging): array
    {
        $lines = [];

        foreach ($wrapped as $offset => $text) {
            $lines[] = $offset === 0 && $marker !== null
                ? $this->spaces($indent) . $marker['marker'] . ' ' . $text
                : $this->spaces($hanging) . $text;
        }

        return $lines;
    }

    /**
     * Whether every reflowed line still classifies as it must, so a further
     * pass would leave it unchanged.
     *
     * @param  list<string>  $lines
     * @param  array{marker: string, indent: int, width: int}|null  $marker
     * @return bool
     */
    private function isStable(array $lines, ?array $marker): bool
    {
        foreach ($lines as $offset => $line) {
            $expected = $offset === 0 && $marker !== null ? CommentLineClassifier::LIST : CommentLineClassifier::PROSE;

            if ($this->classifier->classify($line, false) !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * The input offsets that overflow the width and those that wrap before they
     * need to.
     *
     * @param  list<string>  $slice
     * @param  int  $marginWidth
     * @return array{long: list<int>, premature: list<int>}
     */
    private function faults(array $slice, int $marginWidth): array
    {
        $long = array_keys(array_filter(
            $slice,
            fn (string $content): bool => $marginWidth + mb_strlen($content) > $this->maxLength,
        ));

        $premature = array_values(array_filter(
            array_keys($slice),
            fn (int $offset): bool => $this->wrapsEarly($slice, $offset, $marginWidth),
        ));

        return ['long' => $long, 'premature' => $premature];
    }

    /**
     * Whether the line at the offset could hold the first word of the next line
     * within the width.
     *
     * @param  list<string>  $slice
     * @param  int  $offset
     * @param  int  $marginWidth
     * @return bool
     */
    private function wrapsEarly(array $slice, int $offset, int $marginWidth): bool
    {
        if ($offset + 1 >= count($slice)) {
            return false;
        }

        $next = $this->glue($this->tokenizer->tokenize($slice[$offset + 1]))[0] ?? '';

        return $next !== ''
            && $marginWidth + mb_strlen($slice[$offset]) + 1 + mb_strlen($next) <= $this->maxLength;
    }

    /**
     * Join a paragraph's lines into a single string, stripping any list marker
     * from the first line.
     *
     * @param  list<string>  $slice
     * @param  array{marker: string, indent: int, width: int}|null  $marker
     * @return string
     */
    private function joinText(array $slice, ?array $marker): string
    {
        $first = $marker === null
            ? ltrim($slice[0])
            : (string) preg_replace('/^\s*([-*+]|\d+[.)])\s+/', '', $slice[0]);

        $rest = array_map(ltrim(...), array_slice($slice, 1));

        return implode(' ', [$first, ...$rest]);
    }

    /**
     * Merge backward any token that must never open a wrapped line, so a
     * reflowed paragraph cannot begin a tag or list.
     *
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private function glue(array $tokens): array
    {
        $out = [];

        foreach ($tokens as $token) {
            if ($out !== [] && $this->isPoison($token)) {
                $out[count($out) - 1] .= ' ' . $token;

                continue;
            }

            $out[] = $token;
        }

        return $out;
    }

    /**
     * Whether a token would re-classify a line if it opened one: a bare tag or
     * a lone list bullet.
     *
     * @param  string  $token
     * @return bool
     */
    private function isPoison(string $token): bool
    {
        return preg_match('/^@[A-Za-z][A-Za-z0-9-]*$/', $token) === 1
            || preg_match('/^([-*+]|\d+[.)])$/', $token)        === 1;
    }

    /**
     * Fill lines greedily with whole tokens up to the width.
     *
     * @param  list<string>  $tokens
     * @param  int  $width
     * @return list<string>
     */
    private function greedy(array $tokens, int $width): array
    {
        $lines   = [];
        $current = '';

        foreach ($tokens as $token) {
            $current = match (true) {
                $current === ''                                       => $token,
                mb_strlen($current) + 1 + mb_strlen($token) <= $width => $current . ' ' . $token,
                default                                               => $this->flush($lines, $current, $token),
            };
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Push the current line and begin the next with the given token.
     *
     * @param  list<string>  $lines
     * @param  string  $current
     * @param  string  $token
     * @return string
     */
    private function flush(array &$lines, string $current, string $token): string
    {
        $lines[] = $current;

        return $token;
    }

    /**
     * The length of the longest token.
     *
     * @param  list<string>  $tokens
     * @return int
     */
    private function longestToken(array $tokens): int
    {
        return max(array_map(mb_strlen(...), $tokens));
    }

    /**
     * The number of leading spaces on a line.
     *
     * @param  string  $text
     * @return int
     */
    private function leadingSpaces(string $text): int
    {
        return mb_strlen($text) - mb_strlen(ltrim($text));
    }

    /**
     * A run of the given number of spaces.
     *
     * @param  int  $count
     * @return string
     */
    private function spaces(int $count): string
    {
        return str_repeat(' ', max(0, $count));
    }
}
