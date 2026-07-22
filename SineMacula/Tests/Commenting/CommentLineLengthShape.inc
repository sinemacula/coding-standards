<?php

namespace App;

/** An opener that carries prose after the open tag is outside the plain shape,
 * so this interior line is left exactly alone even when it runs far beyond the eighty character limit
 */

/**
 * A closer that carries trailing prose is outside the plain docblock shape, so
 * this interior line is left exactly alone even when it runs far beyond the eighty character limit
 * and the whole block stays untouched */

/**
 * An interior line that carries no leading star is outside the plain docblock
 * shape, so this line is left alone even when it runs far beyond the eighty character limit for sure
 no leading star * here
 */

function shape(): void
{
}// A trailing comment after a closing brace is not standalone and is left alone beyond the eighty limit
