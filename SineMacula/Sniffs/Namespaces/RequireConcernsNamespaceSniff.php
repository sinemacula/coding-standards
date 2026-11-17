<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Namespaces;

use SineMacula\CodingStandards\Sniffs\AbstractRequiredNamespaceSniff;

/**
 * Requires every trait to sit under a `Concerns` namespace segment, at any
 * depth (e.g. `App\Billing\Concerns`).
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireConcernsNamespaceSniff extends AbstractRequiredNamespaceSniff
{
    /** @var array<int, int|string> The token a trait declaration opens with. */
    protected const array TOKENS = [T_TRAIT];

    /** @var string The subject noun used in the error message. */
    protected const string SUBJECT = 'Trait';

    /** @var string The sniff error code. */
    protected const string ERROR_CODE = 'NotInConcerns';

    /** @var string The namespace segment that traits must live under. */
    public string $segment = 'Concerns';
}
