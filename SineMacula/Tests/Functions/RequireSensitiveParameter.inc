<?php

namespace App;

class Auth
{
    public function login(string $username, string $password): void
    {
    }

    public function register(string $token, string $secret): void
    {
    }

    public function refresh(string $access_token): void
    {
    }

    public function rotate(#[\SensitiveParameter] string $secret): void
    {
    }

    public function tokenize(string $tokenizer): void
    {
    }
}
