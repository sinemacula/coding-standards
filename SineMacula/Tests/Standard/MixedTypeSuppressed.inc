<?php

declare(strict_types = 1);

namespace App\Payloads;

/**
 * A payload read from an external source.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class Payload
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
    /** @var array<string, mixed> The values a payload falls back to. */
    private const array DEFAULTS = [];

    // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
    /** @var array<string, mixed> The values last decoded. */
    private array $values = self::DEFAULTS;

    /**
     * Decode the given JSON, whose values are whatever the sender sent.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @param  string  $json
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    public function decode(string $json): array
    {
        $this->values = (array) json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $this->values;
    }
}
