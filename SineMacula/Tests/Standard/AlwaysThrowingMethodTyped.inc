<?php

declare(strict_types = 1);

namespace App\Tokens;

use LogicException;

/**
 * A token that refuses to leave the process, declared the long way.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class TypedToken
{
    /**
     * Refuse serialisation while still declaring the type that would be
     * returned if it ever did.
     *
     * @throws \LogicException
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        throw new LogicException('A token must not be serialised.');
    }
}
