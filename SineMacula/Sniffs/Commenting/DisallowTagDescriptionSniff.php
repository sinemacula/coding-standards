<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesTagHead;

/**
 * Tag description sniff.
 *
 * An @param or @throws tag carries no description. The function comment sniff
 * wants one capitalised and closed with a full stop, while the formatter
 * lowercases it, strips the full stop and pads it again, so no description can
 * satisfy both and a bare tag is the only shape the two agree on. The fixer
 * removes the description, its continuation lines included, and leaves the type
 * and variable as written, however many lines the type spans.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class DisallowTagDescriptionSniff implements Sniff
{
    use ResolvesTagHead;

    /** @var array<string, string> The error code reported for each governed tag. */
    private const array CODES = [
        '@param'  => 'ParamFound',
        '@throws' => 'ThrowsFound',
    ];

    /** @var array<string, string> The message reported for each error code. */
    private const array MESSAGES = [
        'ParamFound'  => 'An @param tag must not carry a description; leave the tag bare, as in @param  int  $count.',
        'ThrowsFound' => 'An @throws tag must not carry a description; leave the tag bare, as in @throws \RuntimeException.',
    ];

    /** @var array<string, string> What ends the part of each tag that is not description. */
    private const array HEAD_ENDS = [
        'ParamFound'  => '/\G\$(?!this(?![A-Za-z0-9_\x80-\xff]))[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*/',
        'ThrowsFound' => '/\G(?<![\s|&])(?=\s)(?!\s*[|&])/',
    ];

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_DOC_COMMENT_OPEN_TAG];
    }

    /**
     * Process a doc comment, checking each governed tag it carries.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $tags   = $tokens[$stackPtr]['comment_tags'];
        $bounds = [...array_slice($tags, 1), $tokens[$stackPtr]['comment_closer']];

        foreach ($tags as $index => $tag) {
            $code = self::CODES[$tokens[$tag]['content']] ?? null;

            if ($code === null) {
                continue;
            }

            $this->processTag($phpcsFile, $tag, $bounds[$index], $code);
        }
    }

    /**
     * Report, and fix, a description carried by the tag at the pointer.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $tag
     * @param  int  $bound
     * @param  string  $code
     * @return void
     */
    private function processTag(File $phpcsFile, int $tag, int $bound, string $code): void
    {
        $tokens  = $phpcsFile->getTokens();
        $strings = $this->tagStrings($tokens, $tag, $bound);
        $head    = $this->headEnd($phpcsFile, $strings, self::HEAD_ENDS[$code]);

        if ($head === null) {
            return;
        }

        [$ptr, $offset] = $head;

        $last    = $strings[count($strings) - 1];
        $content = $tokens[$ptr]['content'];

        if ($ptr === $last && trim(substr($content, $offset)) === '') {
            return;
        }

        if (!$phpcsFile->addFixableError(self::MESSAGES[$code], $tag, $code)) {
            return;
        }

        $closes = $tokens[$last + 1]['code'] === T_DOC_COMMENT_CLOSE_TAG;

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($ptr, substr($content, 0, $offset) . ($closes ? ' ' : ''));

        for ($i = $ptr + 1; $i <= $last; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Where the part of the tag that is not description ends, as the text token
     * it ends in and the offset within it, or null when it never ends. The text
     * is read as one run, a line break standing between each token, so a type
     * that spans lines is followed to its close.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  list<int>  $strings
     * @param  string  $pattern
     * @return array{int, int}|null
     */
    private function headEnd(File $phpcsFile, array $strings, string $pattern): ?array
    {
        $lines = $this->tagLines($phpcsFile, $strings);
        $match = $this->matchOutsideBrackets($lines, $pattern);

        if ($match === null) {
            return null;
        }

        $end = $match[0] + strlen($match[1]);

        foreach ($lines as $index => $line) {
            if ($end <= strlen($line)) {
                return [$strings[$index], $end];
            }

            $end -= strlen($line) + 1;
        }

        return null;
    }
}
