<?php

namespace App;

class Config
{
    public int $mutable = 0;

    public readonly string $immutable;

    protected int $hidden = 0;

    private string $secret = '';

    public static int $shared = 0;

    public function __construct(
        public int $promotedMutable,
        public readonly string $promotedImmutable,
        private int $promotedPrivate,
    ) {
        $this->immutable = 'x';
    }
}

final readonly class ValueObject
{
    public int $value;

    public function __construct(
        public string $label,
    ) {
        $this->value = 1;
    }
}
