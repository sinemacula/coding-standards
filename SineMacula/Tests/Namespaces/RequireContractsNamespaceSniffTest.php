<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Namespaces;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the contracts namespace sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
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
        $this->assertErrorsOnLines('RequireContractsNamespace.inc', [5]);
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
}
