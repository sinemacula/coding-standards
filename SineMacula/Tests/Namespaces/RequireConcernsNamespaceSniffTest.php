<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Namespaces;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\CodingStandards\Sniffs\AbstractRequiredNamespaceSniff;
use SineMacula\Sniffs\Namespaces\RequireConcernsNamespaceSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the concerns namespace sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireConcernsNamespaceSniff::class)]
#[CoversClass(AbstractRequiredNamespaceSniff::class)]
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
