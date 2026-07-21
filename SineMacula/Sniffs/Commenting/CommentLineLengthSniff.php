<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Comments\CommentWrapper;

/**
 * Comment line length sniff.
 *
 * Keeps standalone comment prose wrapped to a readable width by filling each
 * line greedily with as many whole words as fit. It reports, and fixes, two
 * faults on their own footings: a line that overflows the width, and a line
 * that wraps earlier than it needs to. Standalone `//` runs and multi-line
 * docblocks are governed; tag lines, suppression directives, fenced or indented
 * code, tables, separators and trailing comments after code are left untouched,
 * as is a line whose overflow is a single unbreakable token such as a long name
 * or URL, and a compact single-line docblock, which the single-line member
 * rules already govern.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class CommentLineLengthSniff implements Sniff
{
    /** @var string The message reported for an over-long comment line. */
    private const string TOO_LONG_MESSAGE = 'Comment line must not exceed %d characters.';

    /** @var string The message reported for a prematurely wrapped comment line. */
    private const string PREMATURE_MESSAGE = 'Comment line wraps before it needs to; the next word fits within %d characters.';

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
        return [T_COMMENT, T_DOC_COMMENT_OPEN_TAG];
    }

    /**
     * Process a comment token, handling a `//` run or a docblock as a whole.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $block = $this->resolveBlock($phpcsFile, $stackPtr);

        if ($block === null) {
            return;
        }

        $this->handle($phpcsFile, $block);
    }

    /**
     * Resolve the comment block at the pointer: a docblock, the start of a `//`
     * run, or null when the token begins neither.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return array<string, mixed>|null
     */
    private function resolveBlock(File $phpcsFile, int $stackPtr): ?array
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_DOC_COMMENT_OPEN_TAG) {
            return $this->docBlock($phpcsFile, $stackPtr);
        }

        return $this->isFirstSlash($phpcsFile, $stackPtr) ? $this->slashBlock($phpcsFile, $stackPtr) : null;
    }

    /**
     * Reflow a block, reporting each fault and applying the fix once when the
     * fixer is running.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  array<string, mixed>  $block
     * @return void
     */
    private function handle(File $phpcsFile, array $block): void
    {
        $result = (new CommentWrapper($this->maxLength))->wrap($block['content'], $block['margin']);

        if ($result['long'] === [] && $result['premature'] === []) {
            return;
        }

        if (!$this->reportFaults($phpcsFile, $block, $result)) {
            return;
        }

        $this->applyFix($phpcsFile, $block, $this->rebuild($phpcsFile, $block, $result['lines']));
    }

    /**
     * Report each overflowing and prematurely wrapped line, returning whether
     * the fixer wants the fix applied.
     *
     * @imperative
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  array<string, mixed>  $block
     * @param  array{lines: list<string>, long: list<int>, premature: list<int>}  $result
     * @return bool
     */
    private function reportFaults(File $phpcsFile, array $block, array $result): bool
    {
        $fix = false;

        foreach ($result['long'] as $index) {
            $long = $phpcsFile->addFixableError(self::TOO_LONG_MESSAGE, $block['ptrs'][$index], 'TooLong', [$this->maxLength]);
            $fix  = $fix || $long;
        }

        foreach ($result['premature'] as $index) {
            $early = $phpcsFile->addFixableError(self::PREMATURE_MESSAGE, $block['ptrs'][$index], 'PrematurelyWrapped', [$this->maxLength]);
            $fix   = $fix || $early;
        }

        return $fix;
    }

    /**
     * Replace the block's tokens with the reflowed text. The caller only fixes
     * a block that has a fault, so the text always differs from the original.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  array<string, mixed>  $block
     * @param  string  $text
     * @return void
     */
    private function applyFix(File $phpcsFile, array $block, string $text): void
    {
        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($block['first'], $text);

        for ($ptr = $block['first'] + 1; $ptr <= $block['last']; $ptr++) {
            $phpcsFile->fixer->replaceToken($ptr, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Rebuild a block's source from its reflowed content lines.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  array<string, mixed>  $block
     * @param  list<string>  $lines
     * @return string
     */
    private function rebuild(File $phpcsFile, array $block, array $lines): string
    {
        $eol    = $phpcsFile->eolChar;
        $indent = $block['indent'];

        if ($block['kind'] === 'doc') {
            $body = array_map(static fn (string $line): string => $line === '' ? $indent . '*' : $indent . '* ' . $line, $lines);

            return '/**' . $eol . implode($eol, $body) . $eol . $indent . '*/';
        }

        $body = [];

        foreach ($lines as $offset => $line) {
            $body[] = ($offset === 0 ? '' : $indent) . '//' . ($line === '' ? '' : ' ' . $line);
        }

        return implode($eol, $body) . ($block['trailingEol'] ? $eol : '');
    }

    /**
     * Describe the docblock at the open tag, or null when it is single-line or
     * not the plain house-style shape this sniff reflows.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $openPtr
     * @return array<string, mixed>|null
     */
    private function docBlock(File $phpcsFile, int $openPtr): ?array
    {
        $tokens    = $phpcsFile->getTokens();
        $closePtr  = $tokens[$openPtr]['comment_closer'];
        $openLine  = $tokens[$openPtr]['line'];
        $closeLine = $tokens[$closePtr]['line'];
        $lines     = $this->fileLines($phpcsFile);

        if ($openLine === $closeLine || trim($lines[$openLine - 1]) !== '/**' || trim($lines[$closeLine - 1]) !== '*/') {
            return null;
        }

        $content = $this->docContent($lines, $openLine, $closeLine, $indent);

        return $content === null ? null : [
            'first'   => $openPtr,
            'last'    => $closePtr,
            'content' => $content,
            'ptrs'    => $this->lineMap($phpcsFile, $openPtr, $closePtr, $openLine + 1),
            'margin'  => mb_strlen((string) $indent) + 2,
            'kind'    => 'doc',
            'indent'  => $indent,
        ];
    }

    /**
     * Extract the margin-stripped content of a docblock's interior lines,
     * binding the shared indent, or null when a line breaks the plain shape.
     *
     * @param  array<int, string>  $lines
     * @param  int  $openLine
     * @param  int  $closeLine
     * @param  string|null  $indent
     * @return list<string>|null
     */
    private function docContent(array $lines, int $openLine, int $closeLine, ?string &$indent): ?array
    {
        $indent  = null;
        $content = [];

        for ($line = $openLine + 1; $line < $closeLine; $line++) {
            if (preg_match('/^(\s*)\*( ?)(.*)$/', rtrim($lines[$line - 1] ?? '', "\r"), $match) !== 1) {
                return null;
            }

            $indent ??= $match[1];

            if ($match[1] !== $indent) {
                return null;
            }

            $content[] = $match[3];
        }

        return $content === [] ? null : $content;
    }

    /**
     * Describe the standalone `//` run beginning at the pointer.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $firstPtr
     * @return array<string, mixed>
     */
    private function slashBlock(File $phpcsFile, int $firstPtr): array
    {
        $tokens  = $phpcsFile->getTokens();
        $run     = $this->slashRun($phpcsFile, $firstPtr);
        $lastPtr = $run[count($run) - 1];
        $lines   = $this->fileLines($phpcsFile);
        $first   = $lines[$tokens[$firstPtr]['line'] - 1] ?? '';
        $indent  = mb_substr($first, 0, mb_strlen($first) - mb_strlen(ltrim($first)));
        $content = [];

        foreach ($run as $ptr) {
            $content[] = (string) preg_replace('#^//( )?#', '', rtrim($tokens[$ptr]['content'], "\r\n"));
        }

        return [
            'first'       => $firstPtr,
            'last'        => $lastPtr,
            'content'     => $content,
            'ptrs'        => $run,
            'margin'      => mb_strlen($indent) + 3,
            'kind'        => 'slash',
            'indent'      => $indent,
            'trailingEol' => str_ends_with($tokens[$lastPtr]['content'], "\n"),
        ];
    }

    /**
     * The pointers of the standalone `//` comments forming a run from the
     * pointer down consecutive lines.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $firstPtr
     * @return list<int>
     */
    private function slashRun(File $phpcsFile, int $firstPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $run    = [$firstPtr];
        $ptr    = $firstPtr;

        while (($next = $phpcsFile->findNext(T_COMMENT, $ptr + 1)) !== false) {
            if (
                $tokens[$next]['line']      !== $tokens[$ptr]['line'] + 1
                || $tokens[$next]['column'] !== $tokens[$firstPtr]['column']
                || !$this->isStandaloneSlash($phpcsFile, $next)
            ) {
                break;
            }

            $run[] = $next;
            $ptr   = $next;
        }

        return $run;
    }

    /**
     * Whether the comment begins a standalone `//` run: itself standalone, with
     * no standalone `//` comment on the line directly above.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $ptr
     * @return bool
     */
    private function isFirstSlash(File $phpcsFile, int $ptr): bool
    {
        if (!$this->isStandaloneSlash($phpcsFile, $ptr)) {
            return false;
        }

        $tokens = $phpcsFile->getTokens();
        $above  = $phpcsFile->findPrevious(T_COMMENT, $ptr - 1);

        return $above === false
            || $tokens[$above]['line']   !== $tokens[$ptr]['line'] - 1
            || $tokens[$above]['column'] !== $tokens[$ptr]['column']
            || !$this->isStandaloneSlash($phpcsFile, $above);
    }

    /**
     * Whether the comment is a `//` comment that is the first token on its
     * line.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $ptr
     * @return bool
     */
    private function isStandaloneSlash(File $phpcsFile, int $ptr): bool
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$ptr]['code'] !== T_COMMENT || !str_starts_with($tokens[$ptr]['content'], '//')) {
            return false;
        }

        $before = $phpcsFile->findPrevious(T_WHITESPACE, $ptr - 1, null, true);

        return $before === false || $tokens[$before]['line'] !== $tokens[$ptr]['line'];
    }

    /**
     * Map each content line, from the given first line, to a token on its line
     * for reporting.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $from
     * @param  int  $to
     * @param  int  $firstLine
     * @return list<int>
     */
    private function lineMap(File $phpcsFile, int $from, int $to, int $firstLine): array
    {
        $tokens = $phpcsFile->getTokens();
        $byLine = [];

        for ($ptr = $from; $ptr <= $to; $ptr++) {
            $byLine[$tokens[$ptr]['line']] ??= $ptr;
        }

        $ptrs = [];

        for ($line = $firstLine; $line < $tokens[$to]['line']; $line++) {
            $ptrs[] = $byLine[$line] ?? $from;
        }

        return $ptrs;
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
}
