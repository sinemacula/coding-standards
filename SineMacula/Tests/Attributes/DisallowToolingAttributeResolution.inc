<?php

namespace App;

use JetBrains as JB;
use JetBrains\PhpStorm\Pure as NotPure;
use \JetBrains\PhpStorm\Deprecated;

class Resolution
{
    #[JB\PhpStorm\Pure]
    public function viaNamespaceAlias(): void
    {
    }

    #[NotPure]
    public function viaClassAlias(): void
    {
    }

    #[Deprecated]
    public function viaLeadingBackslashImport(): void
    {
    }

    #[\JetBrains\PhpStorm]
    public function theNamespaceItself(): void
    {
    }

    #[\JetBrains\PhpStormExtra\Something]
    public function lookalikeNamespace(): void
    {
    }
}
