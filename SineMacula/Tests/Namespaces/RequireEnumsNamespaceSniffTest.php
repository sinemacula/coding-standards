<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Namespaces;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the enums namespace sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class RequireEnumsNamespaceSniffTest extends AbstractSniffTestCase
{
    /**
     * Enums outside an Enums namespace are flagged; those inside one (at any
     * depth) are not.
     *
     * @return void
     */
    public function testFlagsEnumsOutsideEnumsNamespace(): void
    {
        $this->assertErrorsOnLines('RequireEnumsNamespace.inc', [5]);
    }

    /**
     * An enum in the global namespace (no namespace declaration) is also
     * flagged.
     *
     * @return void
     */
    public function testFlagsEnumInGlobalNamespace(): void
    {
        $this->assertErrorsOnLines('RequireEnumsNamespaceGlobal.inc', [3]);
    }
}
