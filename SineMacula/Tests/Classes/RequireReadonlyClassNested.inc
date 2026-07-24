<?php

namespace App;

final class OuterReadonly
{
    public readonly int $value;

    public function make(): void
    {
        $local = 0;

        class InnerMutable
        {
            public int $flag = 0;

            public function __construct(
                public int $count,
            ) {
            }
        }
    }
}

final class OuterMutable
{
    public int $count = 0;

    public function make(): void
    {
        class InnerReadonly
        {
            public readonly int $value;
        }
    }
}
