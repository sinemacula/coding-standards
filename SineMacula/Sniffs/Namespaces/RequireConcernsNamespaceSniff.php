<?php

declare(strict_types = 1);

namespace SineMacula\Sniffs\Namespaces;

use SineMacula\CodingStandards\Sniffs\AbstractRequiredNamespaceSniff;

/**
 * Concerns namespace sniff.
 *
 * Requires every trait to be declared under a `Concerns` namespace segment (at
 * any depth, e.g. `App\Concerns` or `App\Billing\Concerns`), keeping reusable
 * behaviour grouped and discoverable, mirroring the Contracts rule for
 * interfaces.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class RequireConcernsNamespaceSniff extends AbstractRequiredNamespaceSniff
{
    /** @var string The namespace segment that traits must live under. */
    public string $concernsSegment = 'Concerns';

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_TRAIT];
    }

    /**
     * The namespace segment traits must live under.
     *
     * @return string
     */
    #[\Override]
    protected function segment(): string
    {
        return $this->concernsSegment;
    }

    /**
     * The subject noun used in the error message.
     *
     * @return string
     */
    #[\Override]
    protected function subject(): string
    {
        return 'Trait';
    }

    /**
     * The sniff error code.
     *
     * @return string
     */
    #[\Override]
    protected function errorCode(): string
    {
        return 'NotInConcerns';
    }
}
