<?php

namespace App;

class ConfiguredService
{
    public function fail(): void
    {
        throw new \RuntimeException('boom');
    }

    public function stillAllowed(): void
    {
        throw new \Exception('not in the configured list');
    }
}
