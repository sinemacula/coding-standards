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
    /** @var string The namespace segment that interfaces must live under. */
    public string $contractsSegment = 'Contracts';

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_INTERFACE];
    }

    /**
     * The namespace segment interfaces must live under.
     *
     * @return string
     */
    #[\Override]
    protected function segment(): string
    {
        return $this->contractsSegment;
    }

    /**
     * The subject noun used in the error message.
     *
     * @return string
     */
    #[\Override]
    protected function subject(): string
    {
        return 'Interface';
    }

    /**
     * The sniff error code.
     *
     * @return string
     */
    #[\Override]
    protected function errorCode(): string
    {
        return 'NotInContracts';
    }
}
