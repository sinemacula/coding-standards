<?php

namespace App;

class Validator
{
    public function isValid(): bool
    {
        return true;
    }

    public function hasErrors(): bool
    {
        return false;
    }

    public function matches(string $input): bool
    {
        return true;
    }

    public function validate(): bool
    {
        return true;
    }

    public function check(): bool
    {
        return false;
    }

    public function process(): void
    {
    }

    public function count(): int
    {
        return 0;
    }

    public function __isset(string $name): bool
    {
        return false;
    }
}
