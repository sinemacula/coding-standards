<?php

declare(strict_types = 1);

namespace App\Imports;

/**
 * An importer that declares only part of what it throws.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class PartialImporter
{
    /**
     * Import the given payload, refusing an empty one.
     *
     * @param  string  $payload
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function import(string $payload): string
    {
        if ($payload === '') {
            throw new \InvalidArgumentException('The payload must not be empty.');
        }

        if ($payload === 'x') {
            throw new \DomainException('The payload must not be x.');
        }

        return $payload;
    }
}
