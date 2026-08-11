<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Namespaces;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\AbstractRequiredNamespaceSniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;
use SineMacula\Sniffs\Namespaces\RequireContractsNamespaceSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the contracts namespace sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireContractsNamespaceSniff::class)]
#[CoversClass(AbstractRequiredNamespaceSniff::class)]
#[CoversTrait(ResolvesQualifiedNames::class)]
final class RequireContractsNamespaceSniffTest extends AbstractSniffTestCase
{
    /**
     * Interfaces outside a Contracts namespace are flagged; those inside one
     * (at any depth) are not.
     *
     * @return void
     */
    public function testFlagsInterfacesOutsideContractsNamespace(): void
    {
        $this->assertErrorMessagesOnLines('RequireContractsNamespace.inc', [
            5 => ['Interface "Invoice" must be declared in a "Contracts" namespace.'],
        ]);
    }

    /**
     * The reported code names the segment the declaration is missing, so a
     * ruleset can silence this sniff alone without reaching for the others.
     *
     * @return void
     */
    public function testReportsItsOwnErrorCode(): void
    {
        $this->assertErrorCodesOnLines('RequireContractsNamespace.inc', [5 => ['NotInContracts']]);
    }

    /**
     * An interface in the global namespace (no namespace declaration) is also
     * flagged.
     *
     * @return void
     */
    public function testFlagsInterfaceInGlobalNamespace(): void
    {
        $this->assertErrorsOnLines('RequireContractsNamespaceGlobal.inc', [3]);
    }

    /**
     * An interface inside a braced global namespace is flagged even when the
     * opening brace directly follows the namespace keyword and the interface
     * shares the segment's name.
     *
     * @return void
     */
    public function testFlagsInterfaceInBracedGlobalNamespace(): void
    {
        $this->assertErrorsOnLines('RequireContractsNamespaceBracedGlobal.inc', [4]);
    }
}
