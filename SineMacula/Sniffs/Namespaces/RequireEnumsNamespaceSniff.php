<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Namespaces;

use SineMacula\CodingStandards\Sniffs\AbstractRequiredNamespaceSniff;

/**
 * Enums namespace sniff.
 *
 * Requires every enum to be declared under an `Enums` namespace segment (at any
 * depth, e.g. `App\Enums` or `App\Billing\Enums`), keeping enumerations grouped
 * and discoverable, mirroring the Contracts rule for interfaces.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireEnumsNamespaceSniff extends AbstractRequiredNamespaceSniff
{
    /** @var array<int, int|string> The token an enum declaration opens with. */
    protected const array TOKENS = [T_ENUM];

    /** @var string The subject noun used in the error message. */
    protected const string SUBJECT = 'Enum';

    /** @var string The sniff error code. */
    protected const string ERROR_CODE = 'NotInEnums';

    /** @var string The namespace segment that enums must live under. */
    public string $segment = 'Enums';
}
