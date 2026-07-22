<?php

namespace SineMacula\Tests\PHPStan\Data;

class Counter
{
    private static int $count = 0;

    protected static array $default = ['a'];

    /** @managed-static */
    private static array $memo = [];

    public static int $shared = 0;

    public int $value = 0;

    public static function bump(): void
    {
        self::$count++;
        self::$memo['k'] = 1;
        $local       = 1;
        $cls         = self::class;
        $cls::$count = $local;
    }

    public function readDefault(): array
    {
        return static::$default;
    }
}

final class Mutator
{
    public function set(): void
    {
        Counter::$shared = 5;
    }
}

/**
 * @managed-static
 */
final class Registry
{
    private static array $items = [];
}

final class NoStatics
{
    public int $only = 0;
}

$anon = new class {
    public static int $z = 0;
};

class ChildClassTest
{
    public static string $childClass = '';

    public function configure(): void
    {
        self::$childClass = 'Foo';
    }
}

class SchemaCase extends TestCase
{
    public static array $schema = [];

    public function rebuild(): void
    {
        self::$schema = ['x'];
    }
}

class Gauges
{
    public static int $compound = 0;

    public static int $preIncremented = 0;

    public static int $preDecremented = 0;

    public static int $postDecremented = 0;

    public static array $buffer = [];

    public static ?object $holder = null;

    public static int $first = 0, $second = 0;

    public function touch(): void
    {
        self::$compound += 1;
        ++self::$preIncremented;
        --self::$preDecremented;
        self::$postDecremented--;
        self::$buffer['k'][] = 'x';
        self::$holder->count = 1;
        self::$first  = 1;
        self::$second = 2;
    }
}
