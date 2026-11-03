<?php

declare(strict_types = 1);

namespace App\Reports;

/**
 * A report builder whose parameter types span several lines.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class ReportBuilder
{
    /**
     * Build a report from one row of each shape.
     *
     * @param  array{
     *     name: string,
     *     value: int
     * }  $data
     * @param  list<array{
     *     name: string
     * }> $rows
     * @param  array<string, array<int,
     *     list<string>>>  $groups
     * @param  int  $limit
     * @return string
     */
    public function build(array $data, array $rows, array $groups, int $limit): string
    {
        return sprintf('%s %d %d %d %d', $data['name'], $data['value'], count($rows), count($groups), $limit);
    }

    /**
     * Whether the row passes its own check for the given key.
     *
     * @param  array{
     *     name: string,
     *     check: callable(int, string): bool
     * }|null  $row
     * @param  int  $key
     * @return bool
     */
    public function accepts(?array $row, int $key): bool
    {
        return $row !== null && $row['check']($key, $row['name']);
    }

    /**
     * Describe the given item.
     *
     * @param  array{
     *     label: string
     * } $item
     * @return string
     */
    public function describe(array $item): string
    {
        return $item['label'];
    }

    /**
     * Whether the key names the given opening bracket.
     *
     * @param  '('|'['|'{'  $opener
     * @param  int
     *     |string  $key
     * @return bool
     */
    public function opens(string $opener, int|string $key): bool
    {
        return $opener === (string) $key;
    }
}
