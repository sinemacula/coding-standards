<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Namespaces;

use SineMacula\CodingStandards\Sniffs\AbstractRequiredNamespaceSniff;

/**
 * Contracts namespace sniff.
 *
 * Requires every interface to be declared under a `Contracts` namespace segment
 * (at any depth, e.g. `App\Contracts` or `App\Billing\Contracts`), keeping
 * ports and contracts grouped and discoverable.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireContractsNamespaceSniff extends AbstractRequiredNamespaceSniff
{
    /** @var array<int, int|string> The token an interface declaration opens with. */
    protected const array TOKENS = [T_INTERFACE];

    /** @var string The subject noun used in the error message. */
    protected const string SUBJECT = 'Interface';

    /** @var string The sniff error code. */
    protected const string ERROR_CODE = 'NotInContracts';

    /** @var string The namespace segment that interfaces must live under. */
    public string $segment = 'Contracts';
}
