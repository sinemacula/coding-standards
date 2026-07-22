<?php

namespace App;

use App\Safe\Pure;
use App\Attributes\Audited;use JetBrains\PhpStorm\Deprecated;

$probe = new \JetBrains\PhpStorm\Pure();

class UseMap
{
    #[Pure]
    public function nonToolingImportWins(): void
    {
    }

    #[Deprecated]
    public function adjacentSecondImport(): void
    {
    }
}
