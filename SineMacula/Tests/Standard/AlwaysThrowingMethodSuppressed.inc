<?php

declare(strict_types = 1);

namespace App\Tokens;

use LogicException;

/**
 * A base whose default refuses until a subclass supplies one.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class Exporter
{
    /**
     * Refuse the export, keeping the type a subclass is meant to return.
     *
     * @throws \LogicException
     *
     * phpcs:ignore Squiz.Commenting.FunctionComment.InvalidNoReturn
     * @return array<int, string>
     */
    public function export(): array
    {
        throw new LogicException('Not implemented.');
    }
}
