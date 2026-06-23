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

class Entity extends Model
{
    public int $count = 0;
}

class Aggregate extends Base
{
    public int $total = 0;
}

class FakeMailerTest
{
    public bool $sent = false;
}

class SpyHandler extends TestCase
{
    public int $calls = 0;
}
