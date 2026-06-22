<?php

namespace SineMacula\Tests\PHPStan\Data;

final class Counter
{
    private static int $cache = 0;

    /** The shared total. */
    private static int $documented = 0;

    /** @managed-static reset per request */
    private static array $memo = [];

    private int $value = 0;

    public const int MAX = 100;
}

/**
 * @managed-static
 */
final class Registry
{
    private static array $items = [];
}
