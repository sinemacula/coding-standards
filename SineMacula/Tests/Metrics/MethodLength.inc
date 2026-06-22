<?php

namespace App;

class Example
{
    public function longMethod(): void
    {
        $a = 1;
        $b = 2;
        $c = 3;
        $d = 4;
    }

    public function shortMethod(): void
    {
        $x = 1;
    }
}

abstract class Contract
{
    abstract public function pending(): void;
}

class ExampleTest extends TestCase
{
    public function testLongScenario(): void
    {
        $a = 1;
        $b = 2;
        $c = 3;
        $d = 4;
    }
}
