<?php

namespace App\References;

trait MakesItself
{
    public function make(): self
    {
        return new static();
    }
}

final class Maker
{
    public const int VERSION = 1;

    private static int $count = 0;

    public function build(): self
    {
        return new static();
    }

    public function call(): self
    {
        return static::helper();
    }

    public function version(): int
    {
        return static::VERSION;
    }

    public function counted(): int
    {
        return static::$count;
    }

    public function name(): string
    {
        return static::class;
    }

    public function matches(object $other): bool
    {
        return $other instanceof static;
    }

    public function written(): self
    {
        self::helper();

        return new self();
    }

    public static function helper(): self
    {
        return new self();
    }
}

final class UsesTrait
{
    use MakesItself;
}

enum Suit
{
    case HEARTS;

    public function itself(): self
    {
        return static::HEARTS;
    }
}

class Open
{
    public function build(): static
    {
        return new static();
    }

    public function call(): self
    {
        return static::helper();
    }

    public static function helper(): self
    {
        return new self();
    }
}

abstract class Base
{
    public function build(): static
    {
        return new static();
    }
}
