<?php

namespace App;

class Service
{
    /**
     * A multi-line method comment.
     */
    public function ok(): void {}

    /** A single-line method comment. */
    public function bad(): void {}

    public function undocumented(): void {}

    /** A single-line static method. */
    protected static function badStatic(): int
    {
        return 0;
    }
}

interface Contract
{
    /**
     * A multi-line interface method.
     */
    public function ok(): void;

    /** A single-line interface method. */
    public function bad(): void;
}

trait Helper
{
    /** A single-line trait method. */
    public function bad(): void {}
}

/**
 * A multi-line free function comment.
 */
function freeOk(): void {}

/** A single-line free function, left to the author. */
function freeBad(): void {}
