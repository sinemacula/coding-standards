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
        $index     = 0;
        $count     = count($lines);

        while ($index < $count) {
            [$segment, $index, $inFence] = $this->segment($lines, $index, $inFence, $marginWidth);

            $out       = [...$out, ...$segment['lines']];
            $long      = [...$long, ...$segment['long']];
            $premature = [...$premature, ...$segment['premature']];
        }

        return ['lines' => $out, 'long' => $long, 'premature' => $premature];
    }

    /**
     * Resolve the next segment of the block: its output and faults, the index
     * to continue from, and the fence state that follows.
     *
     * @param  list<string>  $lines
     * @param  int  $index
     * @param  bool  $inFence
     * @param  int  $marginWidth
     * @return array{array{lines: list<string>, long: list<int>, premature: list<int>}, int, bool}
     */
    private function segment(array $lines, int $index, bool $inFence, int $marginWidth): array
    {
        if ($inFence) {
            return [$this->verbatim($lines[$index]), $index + 1, !$this->classifier->isFence($lines[$index])];
        }

        return match ($this->classifier->classify($lines[$index], false)) {
            CommentLineClassifier::FENCE => [$this->verbatim($lines[$index]), $index + 1, true],
            CommentLineClassifier::PROSE => $this->paragraphAt($lines, $index, null, $marginWidth),
            CommentLineClassifier::LIST  => $this->paragraphAt($lines, $index, $this->classifier->listMarker($lines[$index]), $marginWidth),
            default                      => [$this->verbatim($lines[$index]), $index + 1, false],
        };
    }

    /**
     * Reflow a prose paragraph or list item beginning at the index. A list line
     * always parses to a marker, so it never reaches the plain-prose branch.
     *
     * @param  list<string>  $lines
     * @param  int  $start
     * @param  array{marker: string, indent: int, width: int}|null  $marker
     * @param  int  $marginWidth
     * @return array{array{lines: list<string>, long: list<int>, premature: list<int>}, int, bool}
     */
    private function paragraphAt(array $lines, int $start, ?array $marker, int $marginWidth): array
    {
        $end   = $this->proseEnd($lines, $marker === null ? $start : $start + 1);
        $slice = array_slice($lines, $start, $end - $start);

        return [$this->paragraphs->reflow($slice, $marker, $marginWidth, $start), $end, false];
    }

    /**
     * The index one past the last prose line of a paragraph, given the index of
     * its first continuation candidate.
     *
     * @param  list<string>  $lines
     * @param  int  $from
     * @return int
     */
    private function proseEnd(array $lines, int $from): int
    {
        $end = $from;

        while ($end < count($lines) && $this->classifier->classify($lines[$end], false) === CommentLineClassifier::PROSE) {
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
