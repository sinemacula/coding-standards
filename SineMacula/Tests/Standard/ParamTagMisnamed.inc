<?php

declare(strict_types = 1);

namespace App\Imports;

/**
 * An importer whose tags name the wrong variables.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class MisnamedImporter
{
    /**
     * Import the given payload.
     *
     * @param  string  $body
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
     * }  $record
     * @return string
     */
    public function importRow(array $row): string
    {
        return $row['name'];
    }
}
