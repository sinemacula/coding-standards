<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Comments;

/**
 * Classifies a single content line of a comment.
 *
 * Only prose and list items are reflowed; every other kind is preserved
 * verbatim and acts as a paragraph boundary. Directives, docblock tags, fenced
 * or indented code, tables and rule separators are left exactly as written, so
 * a reflow never disturbs a construct whose position or spacing carries
 * meaning.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class CommentLineClassifier
{
    /** @var string A blank line, ending the surrounding paragraph. */
    public const string BLANK = 'blank';

    /** @var string A line of ordinary prose, eligible for reflow. */
    public const string PROSE = 'prose';

    /** @var string A list item, reflowed with a hanging indent. */
    public const string LIST = 'list';

    /** @var string A documentation tag line, left verbatim. */
    public const string TAG = 'tag';

    /** @var string A tool directive line, left verbatim. */
    public const string DIRECTIVE = 'directive';

    /** @var string A rule of separator characters, left verbatim. */
    public const string SEPARATOR = 'separator';

    /** @var string A Markdown table row, left verbatim. */
    public const string TABLE = 'table';

    /** @var string A fenced-code delimiter or a line within one, left verbatim. */
    public const string FENCE = 'fence';

    /** @var string A line of code, left verbatim. */
    public const string CODE = 'code';

    /** @var string A Markdown heading, a hard break left verbatim. */
    public const string HEADING = 'heading';

    /** @var string A fenced-code delimiter opening or closing a verbatim block. */
    private const string FENCE_PATTERN = '/^(```|~~~)/';

    /** @var string A Markdown heading: one to six leading hashes then a space. */
    private const string HEADING_PATTERN = '/^#{1,6}\s/';

    /** @var string A leading bare documentation tag, such as `@param` or `@return`. */
    private const string TAG_PATTERN = '/^@[A-Za-z][A-Za-z0-9-]*(?=\s|$)/';

    /** @var string A list marker: a bullet or an ordered number, then a space. */
    private const string LIST_PATTERN = '/^\s*([-*+]|\d+[.)])\s+/';

    /** @var string A leading tool directive whose position or wording must not change. */
    private const string DIRECTIVE_PATTERN = '/^(phpcs:|phpstan-ignore|@phpstan-|@psalm-|@phan-|eslint\b|globals?\b'
        . '|exported\b|biome-ignore\b|@ts-|prettier-ignore|stylelint-|Stryker (?:disable|restore)\b'
        . '|(?:c8|v8|istanbul) ignore\b|@vite-ignore\b|webpackChunkName\b|@preserve\b|@license\b'
        . '|@codingStandards|@SuppressWarnings|NOSONAR|qlty-ignore)/';

    /** @var string A run of rule characters with no letters or digits. */
    private const string SEPARATOR_PATTERN = '/^[=\-~*_#.+ ]{3,}$/';

    /** @var string Code syntax that marks a line as code rather than prose. */
    private const string CODE_PATTERN = '/=>|->|::|;\s*$|\{\s*$|^}'
        . '|^(?:if|elseif|for|foreach|while|switch|catch)\s*\('
        . '|^[\w$>\[\]\'.-]+\s*=[^=>]|^\$/';

    /** @var string Inline atomic spans whose contents are not code: backticks, {@tags} and links. */
    private const string SPAN_PATTERN = '/`[^`]*`|\{@[^}]*\}|\[[^\]]*\]\([^)]*\)/';

    /** @var string A documentation tag whose value may be a structured type expression. */
    private const string TYPE_TAG_PATTERN = '/^@(?:(?:phpstan|psalm|phan)-(?:type|import-type|param|return|var)'
        . '|var|param|return|typedef|type|property|returns)\b/';

    /** @var string A type slot cut off mid-construct, opening a multi-line type value. */
    private const string TYPE_CONTINUES_PATTERN = '/[{<(,:|&]\s*$/';

    /** @var string A single or double quoted string literal within a type value. */
    private const string QUOTE_PATTERN = '/\'[^\']*\'|"[^"]*"/';

    /** @var string A leading tab or two or more spaces, marking an indented code block. */
    private const string INDENT_PATTERN = '/^(?: {2,}|\t)/';

    /**
     * Classify a content line, given whether it sits inside a fenced block.
     *
     * @param  string  $content
     * @param  bool  $inFence
     * @return string
     */
    public function classify(string $content, bool $inFence): string
    {
        $trimmed = trim($content);

        return match (true) {
            $inFence        => self::FENCE,
            $trimmed === '' => self::BLANK,
            default         => $this->kindOf($content, $trimmed),
        };
    }

    /**
     * Whether a content line opens or closes a fenced block.
     *
     * @param  string  $content
     * @return bool
     */
    public function isFence(string $content): bool
    {
        return $this->matches(self::FENCE_PATTERN, trim($content));
    }

    /**
     * Parse a list marker from a content line: the bullet or ordinal, the
     * indent before it and the width it occupies with its trailing space.
     *
     * @param  string  $content
     * @return array{marker: string, indent: int, width: int}|null
     */
    public function listMarker(string $content): ?array
    {
        if (preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $content, $match) !== 1) {
            return null;
        }

        return [
            'marker' => $match[2],
            'indent' => mb_strlen($match[1]),
            'width'  => mb_strlen($match[2]) + 1,
        ];
    }

    /**
     * Whether a content line is indented past the prose baseline - a leading
     * tab or two or more spaces - marking it as a verbatim code or command
     * block.
     *
     * @param  string  $content
     * @return bool
     */
    public function indented(string $content): bool
    {
        return $this->matches(self::INDENT_PATTERN, $content);
    }

    /**
     * The number of leading spaces on a content line, for comparing a list
     * item's continuation indent against its hanging indent.
     *
     * @param  string  $content
     * @return int
     */
    public function indentWidth(string $content): int
    {
        return mb_strlen($content) - mb_strlen(ltrim($content, ' '));
    }

    /**
     * Whether a line ends the verbatim type block that precedes it: a blank
     * line or a fresh tag, neither of which a bracketed type ever spans.
     *
     * @param  string  $content
     * @return bool
     */
    public function isTypeBoundary(string $content): bool
    {
        $trimmed = trim($content);

        return $trimmed === '' || str_starts_with($trimmed, '@');
    }

    /**
     * The depth a type-annotation tag's bracketed value opens on this line, or
     * zero when the line is not a type tag or its type closes on the same line.
     * Only the type slot is considered, so a stray bracket in the description
     * that follows the variable never opens a block.
     *
     * @param  string  $content
     * @return int
     */
    public function typeTagDepth(string $content): int
    {
        if (!$this->matches(self::TYPE_TAG_PATTERN, trim($content))) {
            return 0;
        }

        $delta = $this->bracketDelta($content);

        return $delta > 0 && $this->matches(self::TYPE_CONTINUES_PATTERN, $this->typeSlot($content)) ? $delta : 0;
    }

    /**
     * The net depth the type slot of a line opens: braces, angle brackets and
     * parentheses opened less those closed, ignoring inline spans and the
     * object and double-arrow operators.
     *
     * @param  string  $text
     * @return int
     */
    public function bracketDelta(string $text): int
    {
        $stripped = str_replace(['->', '=>'], '', $this->typeSlot($text));

        $opens  = substr_count($stripped, '{') + substr_count($stripped, '<') + substr_count($stripped, '(');
        $closes = substr_count($stripped, '}') + substr_count($stripped, '>') + substr_count($stripped, ')');

        return $opens - $closes;
    }

    /**
     * The portion of a line that carries the type: inline spans removed and
     * everything from the first variable onward dropped, so a bracket in a
     * description never counts toward the type's depth.
     *
     * @param  string  $content
     * @return string
     */
    private function typeSlot(string $content): string
    {
        $stripped = (string) preg_replace([self::SPAN_PATTERN, self::QUOTE_PATTERN], '', $content);
        $variable = mb_strpos($stripped, '$');

        return $variable === false ? $stripped : mb_substr($stripped, 0, $variable);
    }

    /**
     * Classify a non-blank content line by its leading marks, defaulting to
     * prose.
     *
     * @param  string  $content
     * @param  string  $trimmed
     * @return string
     */
    private function kindOf(string $content, string $trimmed): string
    {
        return match (true) {
            $this->matches(self::FENCE_PATTERN, $trimmed)     => self::FENCE,
            $this->matches(self::DIRECTIVE_PATTERN, $trimmed) => self::DIRECTIVE,
            $this->matches(self::TAG_PATTERN, $trimmed)       => self::TAG,
            str_starts_with($trimmed, '|')                    => self::TABLE,
            $this->matches(self::HEADING_PATTERN, $trimmed)   => self::HEADING,
            $this->matches(self::SEPARATOR_PATTERN, $trimmed) => self::SEPARATOR,
            $this->matches(self::LIST_PATTERN, $content)      => self::LIST,
            $this->isCode($trimmed)                           => self::CODE,
            default                                           => self::PROSE,
        };
    }

    /**
     * Whether a line carries code syntax, ignoring inline atomic spans whose
     * contents (backticked code, an FQCN in a `{@link}`) are not code.
     *
     * @param  string  $trimmed
     * @return bool
     */
    private function isCode(string $trimmed): bool
    {
        return $this->matches(self::CODE_PATTERN, (string) preg_replace(self::SPAN_PATTERN, '', $trimmed));
    }

    /**
     * Whether a pattern matches the subject.
     *
     * @param  string  $pattern
     * @param  string  $subject
     * @return bool
     */
    private function matches(string $pattern, string $subject): bool
    {
        return preg_match($pattern, $subject) === 1;
    }
}
