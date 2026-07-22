<?php

namespace App;

final class PlainOnly
{
    public function __construct(
        int $first,
        string $second,
    ) {
    }
}

final class TightComment
{
    public function __construct(/* kept plain */ int $first,
        public readonly int $id,
    ) {
    }
}

final class EdgeComments
{
    public function __construct(
        public readonly int $id,
        /* kept plain */$first,
        string $second,
    ) {
    }
}
