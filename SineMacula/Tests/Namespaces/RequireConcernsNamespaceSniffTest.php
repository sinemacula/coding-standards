<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Namespaces;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\AbstractRequiredNamespaceSniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;
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
#[CoversTrait(ResolvesQualifiedNames::class)]
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
        $this->assertErrorMessagesOnLines('RequireConcernsNamespace.inc', [
            5 => ['Trait "Invoiceable" must be declared in a "Concerns" namespace.'],
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
        $this->assertErrorCodesOnLines('RequireConcernsNamespace.inc', [5 => ['NotInConcerns']]);
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

    /**
     * A trait in the global namespace whose own name matches the required
     * segment is still flagged - the declaration name is not a namespace
     * segment.
     *
     * @return void
     */
    public function testFlagsGlobalTraitNamedAfterSegment(): void
    {
        $this->assertErrorsOnLines('RequireConcernsNamespaceSegmentNamedTrait.inc', [3]);
    }

    /**
     * The required segment may appear at any depth of the namespace, not only
     * as the final segment.
     *
     * @return void
     */
    public function testAllowsSegmentInMiddleOfNamespace(): void
    {
        $this->assertErrorsOnLines('RequireConcernsNamespaceMidSegment.inc', []);
    }
}
