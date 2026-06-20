<?php

namespace App;

final class Service
{
    public function __construct(
        public readonly int $id,
        // The configured timeout.
        int $timeout,
        string $name,
    ) {
    }
}

final class AllPromoted
{
    public function __construct(
        public readonly int $a,
        public readonly int $b,
    ) {
    }
}
