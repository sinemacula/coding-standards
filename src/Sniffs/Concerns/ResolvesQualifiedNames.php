<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

/**
 * Token codes that form a qualified name on both PHP_CodeSniffer 3.x and 4.x.
 *
 * 3.x splits a name into `T_STRING` / `T_NS_SEPARATOR` (and `T_NAMESPACE` for a
 * relative name), while 4.x keeps the single `T_NAME_*` token.
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
