<?php

namespace SineMacula\Tests\PHPStan\Data;

final class Counter
{
    private static int $cache = 0;

    private int $value = 0;

    public const int MAX = 100;
}
