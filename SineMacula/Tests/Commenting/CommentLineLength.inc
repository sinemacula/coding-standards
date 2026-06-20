<?php

namespace App;

// This standalone comment is short and fine.
// This standalone prose comment is intentionally written to run far beyond the eighty character limit.
// See https://example.com/a/very/long/path/that/keeps/going/well/past/the/eighty/character/limit/ok

/**
 * This class summary line is short.
 *
 * This is a long descriptive prose paragraph inside the docblock that runs comfortably beyond eighty.
 *
 * @param \App\Some\Deeply\Nested\Namespace\VeryLongClassName $argument the value to use in the method
 * @return void
 */
class Example
{
    public function run(string $argument): void
    {
        $result = $this->compute($argument); // a trailing comment that pushes this code line beyond eighty
    }
}

//ThisIsASingleVeryLongCommentTokenWithoutAnySpacesThatExceedsTheEightyCharacterLimitForSureXX

?>
<?php

// A short comment in a second PHP block.
