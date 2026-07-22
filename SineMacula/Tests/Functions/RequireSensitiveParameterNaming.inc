<?php

namespace App;

use SensitiveParameter;

class Vault
{
    public function connect(string $apiKey): void
    {
    }

    public function change(string $currentPassword): void
    {
    }

    public function seal(#[SensitiveParameter] string $passphrase): void
    {
    }
}
