<?php

namespace App;

// A short standalone inline comment.

// A standalone comment run that has already been wrapped as tightly as the
// width will allow, so the sniff leaves each of its lines exactly in place.

# A hash-style comment the sniff does not govern, left alone even when it runs on well beyond the eighty character mark.

/* A single-line block comment the sniff does not govern, left alone even when it runs far beyond the eighty-char width. */

/*
 * A multi-line block comment (not a docblock) that the sniff never reflows, so
 * this prose line is left exactly as written even though it runs comfortably beyond the eighty character limit here.
 */

/*
|--------------------------------------------------------------------------
| Laravel Config Style Heading
|--------------------------------------------------------------------------
|
| A framework-style configuration comment block whose lines are left entirely alone, however far beyond eighty they run.
|
*/

/** A single-line docblock the single-line member rule governs, exempt here however far beyond the eighty column it runs. */

/**
 * A class summary that fits on one line.
 *
 * A description paragraph that has already been wrapped greedily so that each
 * of its lines is filled before the next, leaving the sniff nothing to change.
 *
 * - a list item already wrapped as tightly as it can be so the hanging indent
 *   is left exactly where the author placed it within the block here now yes
 *
 * ```
 * $code = inside_a_fence_that_runs_well_beyond_the_eighty_character_limit_for_sure();
 * ```
 *
 * | a | a table row that runs well beyond the eighty character limit for a row |
 * ==========================================================================================
 *
 * @param  \App\Very\Long\Namespace\ClassName  $argument  a tag line left alone however far beyond eighty it runs on
 * @return void
 */
final class Example
{
    #[\Override]
    public function run(string $argument): void
    {
        return; // a trailing comment after code that the sniff never governs, left alone beyond eighty for sure now ok.
    }
}

/**
 * A summary that fits on one line.
 *
 * ### Relation existence
 * Prose after a heading is a new paragraph, never pulled up onto the heading.
 *
 * Regenerate and upload the runtime configuration with these two commands:
 *
 *   node gen.mjs > runtime-env.json
 *   aws s3 cp runtime-env.json "s3://a-very-long-bucket-name/path/env.json" --cache-control no-store
 *
 * @phpstan-type ServiceHooks array{
 *     authorize: \Closure(): void,
 *     validate: \Closure(): void,
 *     concerns: array<int, class-string<X>>,
 * }
 */
function documented(): void
{
}

/**
 A malformed docblock whose interior line carries no leading star, so the sniff
 cannot classify its shape and leaves the whole block untouched even when a line runs well beyond eighty characters.
 */

/**
 * A docblock whose interior lines carry inconsistent indentation before the
  * star, so the sniff leaves the whole block untouched however far the lines run beyond the eighty character limit.
 */
