<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Namespaces;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the concerns namespace sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class RequireConcernsNamespaceSniffTest extends AbstractSniffTestCase
{
    /**
     * Traits outside a Concerns namespace are flagged; those inside one (at any
     * depth) are not.
     *
     * @return void
     */
    public function testFlagsTraitsOutsideConcernsNamespace(): void
    {
        $this->assertErrorsOnLines('RequireConcernsNamespace.inc', [5]);
    }

    /**
     * A trait in the global namespace (no namespace declaration) is also
     * flagged.
     *
     * @return void
     */
    public function testFlagsTraitInGlobalNamespace(): void
    {
        $this->assertErrorsOnLines('RequireConcernsNamespaceGlobal.inc', [3]);
    }
}
