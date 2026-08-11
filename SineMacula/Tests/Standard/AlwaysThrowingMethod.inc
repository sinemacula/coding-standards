<?php

declare(strict_types = 1);

namespace App\Tokens;

use App\Contracts\Exportable;
use LogicException;

/**
 * A token that refuses to leave the process.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class Token implements Exportable
{
    /**
     * Refuse the export the interface declares as an array. `never` is a
     * subtype of every return type, so narrowing to it satisfies the inherited
     * signature.
     *
     * @throws \LogicException
     *
     * @return never
     */
    public function export(): never
    {
        throw new LogicException('A token must not be exported.');
    }

    /**
     * Refuse serialisation, so the value cannot reach a queue or a cache.
     *
     * @throws \LogicException
     *
     * @return never
     */
    public function __serialize(): never
    {
        throw new LogicException('A token must not be serialised.');
    }

    /**
     * Refuse the array cast the same way, on a method the class owns outright.
     *
     * @throws \LogicException
     *
     * @return never
     */
    public function toArray(): never
    {
        throw new LogicException('A token must not be flattened.');
    }
}
