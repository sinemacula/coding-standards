<?php

namespace App;

class Config
{
    /** A single-line property. */
    public int $ok = 1;

    /**
     * A multi-line property.
     */
    public int $bad = 2;

    /** A readonly nullable property. */
    public readonly ?string $maybe = null;

    /**
     * A static, union-typed property.
     */
    private static int|string $mixed = 0;

    /** A single-line constant. */
    public const int OK_CONST = 1;

    /**
     * A multi-line constant.
     */
    final protected const string BAD_CONST = 'x';

    private int $undocumented = 3;

    public function work(): void
    {
        /**
         * A documented local variable is not a member.
         */
        $local = 1;
    }
}

enum Status: string
{
    /** The active state. */
    case ACTIVE = 'active';

    /**
     * The inactive state.
     */
    case INACTIVE = 'inactive';

    case UNDOCUMENTED = 'unknown';
}
