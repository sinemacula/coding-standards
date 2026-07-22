<?php

namespace App;

class ConnectionFactory
{
    public function make(): void
    {
        class InlineRedactionTest
        {
            public function record(string $secret): void
            {
            }
        }
    }
}
