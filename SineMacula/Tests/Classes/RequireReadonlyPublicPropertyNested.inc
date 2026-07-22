<?php

namespace App;

readonly class OuterImmutable
{
    public function boot(): void
    {
        class InnerOfImmutable
        {
            public int $mutable = 0;
        }
    }
}

class OuterEntity extends Model
{
    public function boot(): void
    {
        class InnerOfEntity
        {
            public int $mutable = 0;
        }
    }
}

class OuterSpyTest
{
    public function boot(): void
    {
        class InnerOfSpy
        {
            public int $mutable = 0;
        }
    }
}
