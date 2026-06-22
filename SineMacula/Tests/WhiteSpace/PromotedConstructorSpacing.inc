<?php

namespace App;

class GoodCtor
{
    public function __construct(

        /** The id. */
        public int $id,

        /** The name. */
        public string $name,
    ) {
    }
}

class BadCtor
{
    public function __construct(
        /** The id. */
        public int $id,
        /** The name. */
        public string $name,
    ) {
    }
}

class SingleLine
{
    public function __construct(public int $id) {}
}

class NoPromotion
{
    public function __construct(
        int $a,
        int $b,
    ) {
    }
}

class MixedNoTrailingComma
{
    public function __construct(

        /** The id. */
        public int $id,

        // The runtime context.
        string $context
    ) {
    }
}
