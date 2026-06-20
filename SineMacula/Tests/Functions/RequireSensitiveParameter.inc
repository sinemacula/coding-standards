<?php

namespace App;

class Auth
{
    public function login(string $username, #[\SensitiveParameter] string $password): void
    {
    }

    public function authenticate(string $token): void
    {
    }

    public function configure(#[\SensitiveParameter] string $apiKey, int $timeout): void
    {
    }

    public function tokenize(string $tokenizer): void
    {
    }
}
