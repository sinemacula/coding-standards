<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesTagHead;

/**
 * Param tag sniff.
 *
 * A documented function names each of its parameters in an @param tag of its
 * own, in the order the signature declares them. Unlike the core function
 * comment sniff, which reads a tag from its first line, a type that spans lines
 * is followed to its close before the variable is looked for, so an array shape
 * or a callable signature written one member to a line keeps its variable, and
 * a variable inside the type's brackets is never taken for the tag's own. The
 * codes and messages are the core sniff's, and its other checks still apply.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireParamTagSniff implements Sniff
{
    use ResolvesDocComment, ResolvesTagHead;

    /** @var array<int, int|string> Function modifiers a doc comment sits above. */
    private const array MODIFIERS = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT];

    /** @var string A tag's variable, with any reference and variadic marks and up to the next space. */
    private const string VARIABLE = '/\G[&.]*\$(?!this(?![A-Za-z0-9_\x80-\xff]))\S+/';

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * Process a function declaration token.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $stackPtr
     * @return void
     */
    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        $closer = $this->docCommentCloser($phpcsFile, $stackPtr, self::MODIFIERS);

        if ($closer === null) {
            return;
        }

        $opener     = $phpcsFile->getTokens()[$closer]['comment_opener'];
        $parameters = $phpcsFile->getMethodParameters($stackPtr);
        $documented = $this->documentedNames($phpcsFile, $opener, $parameters);

        foreach (array_diff(array_map($this->parameterName(...), $parameters), $documented) as $name) {
            $phpcsFile->addError('Doc comment for parameter "%s" missing', $opener, 'MissingParamTag', [$name]);
        }
    }

    /**
     * The names the @param tags of the doc comment at the pointer document,
     * each tag checked against the parameter at its own position.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $opener
     * @param  array<int, array<string, mixed>>  $parameters
     * @return list<string>
     */
    private function documentedNames(File $phpcsFile, int $opener, array $parameters): array
    {
        $tokens     = $phpcsFile->getTokens();
        $tags       = $tokens[$opener]['comment_tags'];
        $bounds     = [...array_slice($tags, 1), $tokens[$opener]['comment_closer']];
        $documented = [];
        $position   = 0;

        foreach ($tags as $index => $tag) {
            if ($tokens[$tag]['content'] !== '@param') {
                continue;
            }

            $name = $this->tagVariable($phpcsFile, $tag, $bounds[$index]);

            if ($name === '') {
                $phpcsFile->addError('Missing parameter name', $tag, 'MissingParamName');
            } elseif ($name !== null) {
                $documented[] = $this->checkName($phpcsFile, $tag, $name, $parameters[$position] ?? null);
            }

            $position++;
        }

        return $documented;
    }

    /**
     * The variable the tag at the pointer names, an empty string when it names
     * none, or null when the tag has no type before it, which the core sniff
     * reports as a missing type.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $tag
     * @param  int  $bound
     * @return string|null
     */
    private function tagVariable(File $phpcsFile, int $tag, int $bound): ?string
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$tag + 2]['code'] !== T_DOC_COMMENT_STRING) {
            return null;
        }

        $lines = $this->tagLines($phpcsFile, $this->tagStrings($tokens, $tag, $bound));
        $match = $this->matchOutsideBrackets($lines, self::VARIABLE);

        if ($match === null) {
            return '';
        }

        return $match[0] === 0 ? null : $match[1];
    }

    /**
     * Report a tag naming a variable other than the parameter at its position,
     * or naming one where the signature has no parameter left, and return the
     * name it documents.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $tag
     * @param  string  $name
     * @param  array<string, mixed>|null  $parameter
     * @return string
     */
    private function checkName(File $phpcsFile, int $tag, string $name, ?array $parameter): string
    {
        if ($parameter === null) {
            if (!str_ends_with($name, ',...')) {
                $phpcsFile->addError('Superfluous parameter comment', $tag, 'ExtraParamComment');
            }

            return $name;
        }

        $real = $this->parameterName($parameter);
        $bare = str_starts_with($name, '&') ? substr($name, 1) : $name;

        if ($bare !== $name && $parameter['pass_by_reference'] !== true && $bare === $real) {
            $phpcsFile->addError(
                'Doc comment for parameter %s is prefixed with "&" but parameter is not passed by reference',
                $tag,
                'ParamNameUnexpectedAmpersandPrefix',
                [$bare],
            );
        }

        if ($bare !== $real) {
            $this->reportMismatch($phpcsFile, $tag, $bare, $real);
        }

        return $bare;
    }

    /**
     * Report a tag whose variable is not the parameter at its position, telling
     * a difference of case alone apart.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $tag
     * @param  string  $name
     * @param  string  $real
     * @return void
     */
    private function reportMismatch(File $phpcsFile, int $tag, string $name, string $real): void
    {
        $case = strtolower($name) === strtolower($real);

        $phpcsFile->addError(
            'Doc comment for parameter %s does not match ' . ($case ? 'case of ' : '') . 'actual variable name %s',
            $tag,
            $case ? 'ParamNameNoCaseMatch' : 'ParamNameNoMatch',
            [$name, $real],
        );
    }

    /**
     * The name a tag gives the parameter, a variadic one written with its
     * leading ellipsis.
     *
     * @param  array<string, mixed>  $parameter
     * @return string
     */
    private function parameterName(array $parameter): string
    {
        return ($parameter['variable_length'] === true ? '...' : '') . $parameter['name'];
    }
}
