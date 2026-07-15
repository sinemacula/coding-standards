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
    /** @var string The namespace segment that enums must live under. */
    public string $enumsSegment = 'Enums';

    /**
     * Register the tokens this sniff listens for.
     *
     * @return array<int, int|string>
     */
    #[\Override]
    public function register(): array
    {
        return [T_ENUM];
    }

    /**
     * The namespace segment enums must live under.
     *
     * @return string
     */
    #[\Override]
    protected function segment(): string
    {
        return $this->enumsSegment;
    }

    /**
     * The subject noun used in the error message.
     *
     * @return string
     */
    #[\Override]
    protected function subject(): string
    {
        return 'Enum';
    }

    /**
     * The sniff error code.
     *
     * @return string
     */
    #[\Override]
    protected function errorCode(): string
    {
        return 'NotInEnums';
    }
}
