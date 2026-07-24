<?php

namespace App;

use App\Contracts\HttpStatus;

class Config
{
    public const \App\Contracts\ErrorCode CODE = X::Y;

    public const HttpStatus STATUS = X::Y;

    public const \DateTimeInterface CREATED = X::Y;

    public const Contracts\ErrorCode RELATIVE = X::Y;

    public const int NUMBER = 1;

    public const string LABEL = 'x';

    const UNTYPED = 'u';

    public const \App\Types\A|\App\Types\B UNION = X::Y;

    public const \App\Types\C&\App\Types\D INTERSECT = X::Y;

    public const ?\App\Types\N NULLABLE = X::Y;

    public const \App\Types\E|\Throwable MIXED = X::Y;

    public const int FROM_VALUE = \App\Types\Z::NUM;
}

const GLOBAL_CONST = 'x';
