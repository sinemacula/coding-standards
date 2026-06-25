<?php

namespace App;

class Config
{
    /** The maximum number of retries. */
    public const int MAX_RETRIES = 3;

    public const string NAME = 'config';

    private const UNDOCUMENTED = true;

    /** A documented, attribute-tagged constant. */
    #[Internal]
    public const string LABEL = 'config';

    #[Internal]
    public const string SECRET = 'shh';
}

const GLOBAL_CONST = 'x';
