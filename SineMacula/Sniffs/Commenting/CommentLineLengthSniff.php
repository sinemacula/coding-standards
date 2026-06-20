<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Comment line length sniff.
 *
 * Keeps standalone comment lines within a readable width. Docblock tag lines
 * (@param, @return, ...) are exempt because they routinely carry an unbreakable
 * fully qualified name, and any line whose overflow is a single unbreakable
 * token (a long FQCN or URL) is allowed. Trailing comments after code are left
 * to the general line-length rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class CommentLineLengthSniff implements Sniff
{
    /** @var int The maximum number of characters a comment line may occupy. */
    public int $maxLength = 80;

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    /**
     * Process the file once, from its first open tag.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        if ($phpcsFile->findPrevious(T_OPEN_TAG, $stackPtr - 1) !== false) {
            return;
        }

        $lines = $this->fileLines($phpcsFile);

        foreach ($this->commentLines($phpcsFile) as $line => $info) {
            $text = rtrim($lines[$line - 1] ?? '', "\r");

            if ($info['tag'] || mb_strlen($text) <= $this->maxLength || $this->isUnbreakable($text)) {
                continue;
            }

            $phpcsFile->addError(
                'Comment line must not exceed %d characters.',
                $info['ptr'],
                'TooLong',
                [$this->maxLength],
            );
        }
    }

    /**
     * Reconstruct the file as an array of lines.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @return array<int, string>
     */
    private function fileLines(File $phpcsFile): array
    {
        $content = '';

        foreach ($phpcsFile->getTokens() as $token) {
            $content .= $token['content'];
        }

        return explode("\n", $content);
    }

    /**
     * Map each standalone comment line to its first token and tag status.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @return array<int, array{ptr: int, tag: bool}>
     */
    private function commentLines(File $phpcsFile): array
    {
        $tokens = $phpcsFile->getTokens();
        $first  = [];
        $tags   = [];

        foreach ($tokens as $ptr => $token) {
            $line = $token['line'];

            if ($token['code'] === T_DOC_COMMENT_TAG) {
                $tags[$line] = true;
            }

            if (
                isset($first[$line])                                                        !== false
                || in_array($token['code'], [T_WHITESPACE, T_DOC_COMMENT_WHITESPACE], true) !== false
            ) {
                continue;
            }

            $first[$line] = $ptr;
        }

        return $this->filterCommentLines($tokens, $first, $tags);
    }

    /**
     * Keep only the lines that begin with a comment token.
     *
     * @param  array<int, array<string, mixed>>  $tokens
     * @param  array<int, int>  $first
     * @param  array<int, bool>  $tags
     * @return array<int, array{ptr: int, tag: bool}>
     */
    private function filterCommentLines(array $tokens, array $first, array $tags): array
    {
        $starters = [
            T_COMMENT,
            T_DOC_COMMENT_OPEN_TAG,
            T_DOC_COMMENT_CLOSE_TAG,
            T_DOC_COMMENT_STAR,
            T_DOC_COMMENT_STRING,
            T_DOC_COMMENT_TAG,
        ];

        $result = [];

        foreach ($first as $line => $ptr) {
            if (!in_array($tokens[$ptr]['code'], $starters, true)) {
                continue;
            }

            $result[$line] = ['ptr' => $ptr, 'tag' => isset($tags[$line])];
        }

        return $result;
    }

    /**
     * Determine whether an over-long line cannot be wrapped to fit.
     *
     * A line is unbreakable when its overflow is a single token (an FQCN or
     * URL) that would still exceed the limit on a line of its own.
     *
     * @param  string  $text
     * @return bool
     */
    private function isUnbreakable(string $text): bool
    {
        $head     = mb_substr($text, 0, $this->maxLength);
        $space    = mb_strrpos($head, ' ');
        $tab      = mb_strrpos($head, "\t");
        $lastWrap = max($space === false ? -1 : $space, $tab === false ? -1 : $tab);

        if ($lastWrap < 0) {
            return true;
        }

        $indent = mb_strlen($text) - mb_strlen(ltrim($text));
        $tail   = trim(mb_substr($text, $lastWrap + 1));

        return ($indent + mb_strlen($tail)) > $this->maxLength;
    }
}
