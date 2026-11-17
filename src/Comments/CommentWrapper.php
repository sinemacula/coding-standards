<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Comments;

/**
 * Re-wraps a comment block's prose to its greedy canonical form.
 *
 * Optionally, a capitalised line after a finished sentence opens a paragraph,
 * so a short title is not merged into the sentence below.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class CommentWrapper
{
    /** @var string A line ending that leaves its sentence open. */
    private const string OPEN_ENDING_PATTERN = '/(?:[,(-]|\b(?:a|an|the|and|or|but|nor|so|then|of|to|in|on|for|with|by|from|as|at'
        . '|into|than|that|which|what|where|when|while|if|unless|until|since|because|before|after|via|per|is|are|be|not))$/';

    /** @var \SineMacula\CodingStandards\Comments\CommentLineClassifier The line classifier. */
    private CommentLineClassifier $classifier;

    /** @var \SineMacula\CodingStandards\Comments\CommentParagraph The paragraph reflow. */
    private CommentParagraph $paragraphs;

    /**
     * @param  int  $maxLength
     * @param  bool  $capitalOpensParagraph
     * @return void
     */
    public function __construct(

        // Only handed on to the paragraph reflow, which keeps it.
        int $maxLength = 80,

        /** @var bool Whether a capitalised line after a finished one opens a new paragraph. */
        private bool $capitalOpensParagraph = false,
    ) {
        $this->classifier = new CommentLineClassifier;
        $this->paragraphs = new CommentParagraph($maxLength);
    }

    /**
     * Reflow a block's margin-stripped lines, returning the canonical lines and
     * the indices of lines that overflow or wrap early.
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
     * Resolve the next segment: its output, the next index, and the fence and
     * type-block state.
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
     * The verbatim segment for a line inside an open fence or multi-line type,
     * or null when neither is open.
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
     * Dispatch a line by its kind.
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
     * Reflow the prose paragraph or list item beginning at the index.
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
     * The index one past the paragraph's last flush prose line.
     *
     * @param  list<string>  $lines
     * @param  int  $from
     * @return int
     */
    private function flushEnd(array $lines, int $from): int
    {
        $end = $from;

        while ($end < count($lines) && $this->classifier->classify($lines[$end], false) === CommentLineClassifier::PROSE && !$this->classifier->indented($lines[$end])) {
            if ($end > $from && $this->opensParagraph($lines[$end - 1], $lines[$end])) {
                break;
            }

            $end++;
        }

        return $end;
    }

    /**
     * Whether a capitalised line follows a finished sentence.
     *
     * @param  string  $previous
     * @param  string  $line
     * @return bool
     */
    private function opensParagraph(string $previous, string $line): bool
    {
        return $this->capitalOpensParagraph
            && preg_match('/^\p{Lu}/u', $line) === 1
            && preg_match(self::OPEN_ENDING_PATTERN, rtrim($previous)) !== 1;
    }

    /**
     * The index one past a list item's last continuation line.
     *
     * A line indented past the hanging indent is code and ends the item.
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
