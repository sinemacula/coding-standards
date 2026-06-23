<?php

namespace SineMacula\Tests\PHPStan\Data;

class SchemaHelper
{
    public static array $schema = [];

    public function rebuild(): void
    {
        self::$schema = ['x'];
    }
}
