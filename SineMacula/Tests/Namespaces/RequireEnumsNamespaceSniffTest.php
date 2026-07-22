<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Namespaces;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\AbstractRequiredNamespaceSniff;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;
use SineMacula\Sniffs\Namespaces\RequireEnumsNamespaceSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the enums namespace sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireEnumsNamespaceSniff::class)]
#[CoversClass(AbstractRequiredNamespaceSniff::class)]
#[CoversTrait(ResolvesQualifiedNames::class)]
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

    /**
     * The namespace is still resolved when its keyword directly follows the
     * semicolon of the preceding statement with no whitespace between them.
     *
     * @return void
     */
    public function testResolvesNamespaceDirectlyAfterSemicolon(): void
    {
        $this->assertErrorsOnLines('RequireEnumsNamespaceCompactDeclare.inc', []);
    }
}
