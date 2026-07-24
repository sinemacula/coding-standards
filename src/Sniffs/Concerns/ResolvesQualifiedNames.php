<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

/**
 * Reads qualified names across PHP_CodeSniffer 3.x and 4.x.
 *
 * PHP 8 emits a single `T_NAME_*` token per qualified name. PHP_CodeSniffer 3.x
 * backfilled those to `T_STRING` / `T_NS_SEPARATOR` sequences for backwards
 * compatibility, whereas 4.x keeps the whole-name tokens. A sniff that reads a
 * name from the token stream must therefore accept both forms; this trait
 * centralises the set of token codes that make up a name. A relative name
 * (`namespace\Foo`) is one `T_NAME_RELATIVE` on 4.x, but on 3.x its leading
 * `namespace` keyword backfills to `T_NAMESPACE`, so that is included too.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
trait ResolvesQualifiedNames
{
    /**
     * Token codes that can form part of a qualified name.
     *
     * @return array<int, int|string>
     */
    protected function nameTokenCodes(): array
    {
        return [
            T_STRING,
            T_NS_SEPARATOR,
            T_NAMESPACE,
            T_NAME_QUALIFIED,
            T_NAME_FULLY_QUALIFIED,
            T_NAME_RELATIVE,
        ];
    }
}
