<?php

declare(strict_types = 1);

namespace App\Search\Enums;

/**
 * How a term is matched against an indexed column.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
enum Comparison: string
{
    /** Equality against a supplied value, served by a B-tree seek. */
    case EXACT = 'exact';

    /** Prefix match, served by the same index. */
    case PREFIX = 'prefix';
}
