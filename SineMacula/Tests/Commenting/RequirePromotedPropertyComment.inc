<?php

namespace App;

final class User
{
    public function __construct(
        /** The unique identifier. */
        public readonly int $id,
        public readonly string $name,
        string $unpromoted,
    ) {
    }
}
