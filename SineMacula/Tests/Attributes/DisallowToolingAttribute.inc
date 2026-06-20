<?php

namespace App;

use App\Attributes\Audited;
use App\Other\Thing as Alias;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class Service
{
    #[Pure]
    public function compute(): int
    {
        return 1;
    }

    #[\JetBrains\PhpStorm\Deprecated]
    public function old(): void
    {
    }

    #[ArrayShape(['id' => 'int'])]
    public function shape(): array
    {
        return [];
    }

    #[Audited, Override]
    public function audited(): void
    {
    }
}
