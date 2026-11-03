<?php

declare(strict_types = 1);

namespace App\Settings;

/**
 * Settings read from a decoded payload.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class Settings
{
    /**
     * Read the names from the given payload.
     *
     * @param  array<string, array<int, string>>  $payload
     * @return array<int, string>
     */
    public function names(array $payload): array
    {
        /** @var array<int, string> $names */
        $names = $payload['names'] ?? [];

        return $names;
    }
}
