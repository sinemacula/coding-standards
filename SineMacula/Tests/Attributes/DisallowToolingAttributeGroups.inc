<?php

namespace App;

use App\Attributes\Audited;
use JetBrains\PhpStorm\Pure;

class Groups
{
    #[Audited, Pure]
    public function forbiddenSecondInGroup(): void
    {
    }

    #[Audited(Pure)]
    public function argumentsAreNotNames(): void
    {
    }

    #[Pure(true), Audited]
    public function forbiddenFirstWithArguments(): void
    {
    }
}
