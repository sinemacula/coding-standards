<?php

declare(strict_types = 1);

namespace App\Imports;

/**
 * An importer that throws without saying so.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class SilentImporter
{
    /**
     * Import the given payload, refusing an empty one.
     *
     * @param  string  $payload
     * @return string
     */
    public function import(string $payload): string
    {
        if ($payload === '') {
            throw new \InvalidArgumentException('The payload must not be empty.');
        }

        return $payload;
    }
}
