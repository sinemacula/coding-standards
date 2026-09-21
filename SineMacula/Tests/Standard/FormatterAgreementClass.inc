<?php

declare(strict_types = 1);

namespace App\Search;

use App\Search\Concerns\Normalises;
use App\Search\Concerns\Validates;

/**
 * A term to run against the index.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class Term
{
    use Normalises, Validates;

    /** The longest term the index will accept. */
    private const int LIMIT = 64;

    /** @var string The term as supplied, before any normalisation. */
    private string $raw;

    /**
     * Hold the term as supplied.
     *
     * @param  string  $raw
     * @return void
     */
    public function __construct(string $raw)
    {
        $this->raw = $raw;
    }

    /**
     * Whether the term is short enough to run against the index.
     *
     * @return bool
     */
    public function fits(): bool
    {
        return strlen($this->raw) <= self::LIMIT;
    }
}
