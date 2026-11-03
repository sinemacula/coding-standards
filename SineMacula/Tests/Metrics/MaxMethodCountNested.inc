<?php

namespace App;

class Nested
{
    public function a(): object
    {
        return new class () {
            public function inner(): void
            {
            }
        };
    }

    public function b(): void
    {
    }
}
