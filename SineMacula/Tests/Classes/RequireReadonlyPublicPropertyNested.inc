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

class OuterJob implements ShouldQueue
{
    public function boot(): void
    {
        class InnerOfJob
        {
            public int $tries = 0;
        }
    }
}

class OuterQueuedJob implements ShouldQueue
{
    public function boot(): object
    {
        return new class (1) {
            public int $tries = 0;

            public function __construct(public int $timeout)
            {
            }
        };
    }
}
