<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Comments;

/**
 * Re-wraps comment prose to a deterministic greedy canonical form.
 *
 * Given the content lines of one comment block (their margin already stripped)
 * and the display width that margin will reclaim, this walks the block and
 * hands each run of prose and each list item to a paragraph reflow, gathering
 * the canonical lines and the indices of the input lines that overflow or wrap
 * prematurely. Anything that is not plain prose - a tag, a directive, fenced or
 * indented code, a table, a separator - is left verbatim and bounds the
 * paragraph around it.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class CommentWrapper
{
    /** @var \SineMacula\CodingStandards\Comments\CommentLineClassifier The line classifier. */
    private CommentLineClassifier $classifier;

    /** @var \SineMacula\CodingStandards\Comments\CommentParagraph The paragraph reflow. */
    private CommentParagraph $paragraphs;

    /**
     * @param  int  $maxLength
     * @return void
     */
    public function __construct(int $maxLength = 80)
    {
        $this->classifier = new CommentLineClassifier;
        $this->paragraphs = new CommentParagraph($maxLength);
    }

    /**
     * Reflow a block's content lines, returning the canonical lines and the
     * indices of the input lines that overflow or wrap prematurely.
     *
     * @param  list<string>  $lines
     * @param  int  $marginWidth
     * @return array{lines: list<string>, long: list<int>, premature: list<int>}
     */
    public function wrap(array $lines, int $marginWidth): array
    {
        $out       = [];
        $long      = [];
        $premature = [];
        $inFence   = false;
        $typeDepth = 0;
        $index     = 0;
        $count     = count($lines);

        while ($index < $count) {
            [$segment, $index, $inFence, $typeDepth] = $this->segment($lines, $index, $inFence, $typeDepth, $marginWidth);

            $out       = [...$out, ...$segment['lines']];
            $long      = [...$long, ...$segment['long']];
            $premature = [...$premature, ...$segment['premature']];
        }

        return ['lines' => $out, 'long' => $long, 'premature' => $premature];
    }

    /**
     * Resolve the next segment of the block: its output and faults, the index
     * to continue from, and the fence and type-block state that follow.
     *
     * A fenced block, and a type-annotation tag whose value opens a bracketed
     * type across lines, are each left verbatim until they close.
     *
     * @param  list<string>  $lines
     * @param  int  $index
     * @param  bool  $inFence
     * @param  int  $typeDepth
     * @param  int  $marginWidth
     * @return array{array{lines: list<string>, long: list<int>, premature: list<int>}, int, bool, int}
     */
    private function segment(array $lines, int $index, bool $inFence, int $typeDepth, int $marginWidth): array
    {
        $continuation = $this->continuation($lines, $index, $inFence, $typeDepth);

        if ($continuation !== null) {
            return $continuation;
        }

        $opening = $this->classifier->typeTagDepth($lines[$index]);

        if ($opening > 0) {
            return [$this->verbatim($lines[$index]), $index + 1, false, $opening];
        }

        return $this->dispatch($lines, $index, $marginWidth);
    }

    /**
     * The verbatim segment for a line inside an open fenced block or an open
     * type-annotation value, or null when neither block is open at this line.
     *
     * @param  list<string>  $lines
     * @param  int  $index
     * @param  bool  $inFence
     * @param  int  $typeDepth
     * @return array{array{lines: list<string>, long: list<int>, premature: list<int>}, int, bool, int}|null
     */
    private function continuation(array $lines, int $index, bool $inFence, int $typeDepth): ?array
    {
        if ($inFence) {
            return [$this->verbatim($lines[$index]), $index + 1, !$this->classifier->isFence($lines[$index]), 0];
        }

        if ($typeDepth > 0 && !$this->classifier->isTypeBoundary($lines[$index])) {
            $depth = max(0, $typeDepth + $this->classifier->bracketDelta($lines[$index]));

            return [$this->verbatim($lines[$index]), $index + 1, false, $depth];
        }

        return null;
    }

    /**
     * Dispatch a line by its kind: enter a fence, reflow a prose paragraph or
     * list item, or leave the line verbatim.
     *
     * @param  list<string>  $lines
     * @param  int  $index
     * @param  int  $marginWidth
     * @return array{array{lines: list<string>, long: list<int>, premature: list<int>}, int, bool, int}
     */
    private function dispatch(array $lines, int $index, int $marginWidth): array
    {
        if ($this->classifier->indented($lines[$index])) {
            return [$this->verbatim($lines[$index]), $index + 1, false, 0];
        }

        return match ($this->classifier->classify($lines[$index], false)) {
            CommentLineClassifier::FENCE => [$this->verbatim($lines[$index]), $index + 1, true, 0],
            CommentLineClassifier::PROSE => $this->paragraphAt($lines, $index, null, $marginWidth),
            CommentLineClassifier::LIST  => $this->paragraphAt($lines, $index, $this->classifier->listMarker($lines[$index]), $marginWidth),
            default                      => [$this->verbatim($lines[$index]), $index + 1, false, 0],
        };
    }

    /**
     * Reflow a prose paragraph or list item beginning at the index. A prose
     * paragraph is a run of flush prose lines; a list item is its marker line
     * with any continuation lines that stay within its hanging indent. A list
     * line always parses to a marker, so it never reaches the plain-prose
     * branch.
     *
     * @param  list<string>  $lines
     * @param  int  $start
     * @param  array{marker: string, indent: int, width: int}|null  $marker
     * @param  int  $marginWidth
     * @return array{array{lines: list<string>, long: list<int>, premature: list<int>}, int, bool, int}
     */
    private function paragraphAt(array $lines, int $start, ?array $marker, int $marginWidth): array
    {
        $end = $marker === null
            ? $this->flushEnd($lines, $start)
            : $this->continuationEnd($lines, $start + 1, $marker['indent'] + $marker['width']);

        $slice = array_slice($lines, $start, $end - $start);

        return [$this->paragraphs->reflow($slice, $marker, $marginWidth, $start), $end, false, 0];
    }

    /**
     * The index one past the last flush prose line, given the index of the
     * paragraph's first line. An indented line ends the paragraph, bounding a
     * verbatim indented code block beneath it.
     *
     * @param  list<string>  $lines
     * @param  int  $from
     * @return int
     */
    private function flushEnd(array $lines, int $from): int
    {
        $end = $from;

        while ($end < count($lines) && $this->classifier->classify($lines[$end], false) === CommentLineClassifier::PROSE && !$this->classifier->indented($lines[$end])) {
            $end++;
        }

        return $end;
    }

    /**
     * The index one past the last continuation line of a list item, given the
     * index of its first candidate and the item's hanging indent. A line
     * indented past the hanging indent is a verbatim code block, not a
     * continuation, so it ends the item.
     *
     * @param  list<string>  $lines
     * @param  int  $from
     * @param  int  $hanging
     * @return int
     */
    private function continuationEnd(array $lines, int $from, int $hanging): int
    {
        $end = $from;

        while (
            $end < count($lines)
            && $this->classifier->classify($lines[$end], false) === CommentLineClassifier::PROSE
            && $this->classifier->indentWidth($lines[$end]) <= $hanging
        ) {
            $end++;
        }

        return $end;
    }

    /**
     * A segment holding a single verbatim line and no faults.
     *
     * @param  string  $line
     * @return array{lines: list<string>, long: list<int>, premature: list<int>}
     */
    private function verbatim(string $line): array
    {
        return ['lines' => [$line], 'long' => [], 'premature' => []];
    }
}
