<?php

namespace App;

class WrapperTest extends TestCase
{
    public function testDefinesRuntimeClass(): void
    {
        class Runtime
        {
            public function longMethod(): void
            {
                $a = 1;
                $b = 2;
                $c = 3;
                $d = 4;
            }
        }
    }
}
