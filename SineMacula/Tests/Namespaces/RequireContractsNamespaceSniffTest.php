<?php

namespace SineMacula\Tests\Namespaces;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the contracts namespace sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
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
}
