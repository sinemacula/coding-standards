<?php

declare(strict_types = 1);

namespace App\Imports;

/**
 * An importer whose tags name no variable.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class UnnamedImporter
{
    /**
     * Import the given payload.
     *
     * @param  string
     * @return string
     */
    public function import(string $payload): string
    {
        return $payload;
    }

    /**
     * Import the given row.
     *
     * @param  array{
     *     name: string
     * }
     * @return string
     */
    public function importRow(array $row): string
    {
        return $row['name'];
    }
}
