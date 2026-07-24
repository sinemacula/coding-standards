<?php

namespace App;

final class ValueObject
{
    public readonly int $value;

    public readonly string $label;
}

final class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {
    }
}

final class Combined
{
    public readonly int $id;

    public function __construct(
        public readonly string $name,
    ) {
    }
}

final class PrivateState
{
    private readonly int $secret;

    protected readonly string $token;
}

final class WithConstants
{
    public const int VERSION = 1;

    public readonly int $value;
}

final class HasMutable
{
    public readonly int $fixed;

    public int $changing = 0;
}

final class MutablePromoted
{
    public function __construct(
        public readonly int $a,
        public int $b,
    ) {
    }
}

final class HasStatic
{
    public readonly int $value;

    private static int $count = 0;
}

final class OnlyStatic
{
    private static int $count = 0;
}

final class Behaviour
{
    public function run(): void
    {
        $local = 0;
    }
}

final readonly class AlreadyReadonly
{
    public int $value;
}

abstract class AbstractBase
{
    public readonly int $value;
}

final class Entity extends Model
{
    public readonly int $id;
}

final class WidgetTest
{
    public readonly int $value;
}

final class Spy extends TestCase
{
    public readonly int $value;
}

final class Derived extends BaseThing
{
    public readonly int $value;
}

final class MixedCaseConstructor
{
    public function __Construct(
        public readonly int $amount,
    ) {
    }
}

final class PlainAndPromoted
{
    public function __construct(
        int $plain,
        public readonly int $a,
    ) {
    }
}

final class Grouped
{
    public readonly int $x, $y;
}

final class Coordinates implements Arrayable
{
    public readonly float $lat;

    public readonly float $lng;
}

final class Service
{
    public int $calls = 0;

    public function __construct(
        public readonly string $name,
    ) {
    }
}

enum Suit
{
    case HEARTS;
}
