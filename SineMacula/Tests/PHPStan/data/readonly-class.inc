<?php

namespace App;

trait ReadonlyTrait
{
    public readonly int $traitValue;
}

trait MutableTrait
{
    public int $alias;
}

class EmptyBase
{
}

final class Money
{
    public readonly int $amount;

    public readonly string $currency;
}

final class WithReadonlyTrait
{
    use ReadonlyTrait;

    public readonly int $x;
}

final class HasMutable
{
    public readonly int $x;

    public int $y = 0;
}

final class HasStatic
{
    public readonly int $x;

    private static int $count = 0;
}

final class Behaviour
{
    public function run(): void
    {
    }
}

final readonly class AlreadyReadonly
{
    public int $x;
}

class NotFinal
{
    public readonly int $x;
}

abstract class AbstractBase
{
    public readonly int $x;
}

final class Derived extends EmptyBase
{
    public readonly int $x;
}

final class UsesMutableTrait
{
    use MutableTrait;

    public readonly int $x;
}

final class ShapeTest
{
    public readonly int $x;
}

interface Contract
{
    public const int VERSION = 1;
}

enum Suit
{
    case HEARTS;
}
