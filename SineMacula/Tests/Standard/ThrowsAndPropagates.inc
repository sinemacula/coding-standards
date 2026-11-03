<?php

declare(strict_types = 1);

namespace App\Imports;

/**
 * An importer that both throws and lets a decoding failure through.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class Importer
{
    /**
     * Import the given payload, refusing an empty one. The decoding failure
     * comes from the callee and is declared alongside the refusal.
     *
     * @param  string  $payload
     * @return array<int, string>
     *
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function import(string $payload): array
    {
        if ($payload === '') {
            throw new \InvalidArgumentException('The payload must not be empty.');
        }

        return $this->decode($payload);
    }

    /**
     * Decode the given payload into a list of names.
     *
     * @param  string  $payload
     * @return array<int, string>
     *
     * @throws \JsonException
     */
    private function decode(string $payload): array
    {
        return array_map('strval', (array) json_decode($payload, true, 512, JSON_THROW_ON_ERROR));
    }
}
