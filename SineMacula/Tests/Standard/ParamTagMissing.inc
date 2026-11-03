<?php

declare(strict_types = 1);

namespace App\Imports;

/**
 * An importer that leaves a parameter undocumented.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class PartialImporter
{
    /**
     * Import the given payload.
     *
     * @param  string  $payload
     * @return string
     */
    public function import(string $payload, int $limit): string
    {
        return substr($payload, 0, $limit);
    }

    /**
     * Import the given rows.
     *
     * @param  list<array{
     *     name: string
     * }> $rows
     * @return int
     */
    public function importRows(array $rows, int $limit): int
    {
        return min(count($rows), $limit);
    }
}
