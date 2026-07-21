<?php

namespace App;

// A short standalone comment that is well within the width.

// A longer standalone comment that has already been wrapped greedily so that
// every one of its lines is filled as far as the width will allow it to be.

// See https://example.com/a/very/long/path/that/keeps/going/well/past/the/eighty/character/limit/ok

//ThisIsASingleVeryLongCommentTokenWithoutAnySpacesThatExceedsTheEightyCharacterLimitForSureXX

// @phpstan-ignore-next-line because the third-party stub returns mixed when it is really a string value

/**
 * A summary that fits comfortably on one line.
 *
 * A description paragraph that has already been wrapped greedily so that each
 * of its lines is filled with as many whole words as the width will accept
 * before it moves on to the next line of the block.
 *
 * The following renders verbatim and is never reflowed:
 *
 * - a list item already wrapped as tightly as it can be so that the sniff will
 *   leave its hanging indentation exactly as the author has laid it out here
 * - a short item
 *
 * ```
 * $example = code_inside_a_fence_that_runs_well_beyond_the_eighty_character_limit();
 * ```
 *
 * | column a | column b that runs well beyond the eighty character limit for a table |
 * |----------|------------------------------------------------------------------------|
 *
 * ============================================================================================
 *
 * @param  \App\Some\Deeply\Nested\Namespace\VeryLongClassName  $argument  the value to use here
 * @return void
 */
class Valid
{
    /** @var string A single-line docblock that is intentionally written to run beyond the eighty limit ok. */
    public string $name = '';

    public function run(string $argument): void
    {
        $result = $this->compute($argument); // a trailing comment that pushes this code line beyond eighty chars
    }
}
