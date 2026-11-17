<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;
use SineMacula\Sniffs\Attributes\DisallowToolingAttributeSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the tooling attribute sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(DisallowToolingAttributeSniff::class)]
#[CoversTrait(ResolvesQualifiedNames::class)]
final class DisallowToolingAttributeSniffTest extends AbstractSniffTestCase
{
    /** @var array<int, string>|null Per-test forbidden-namespace override. */
    private ?array $forbiddenNamespaces = null;

    /**
     * Only attributes under a forbidden namespace should be flagged, whether
     * imported, fully qualified, or used with arguments.
     *
     * @return void
     */
    public function testFlagsToolingAttributes(): void
    {
        $this->assertErrorsOnLines('DisallowToolingAttribute.inc', [12, 18, 23]);
    }

    /**
     * The message names the attribute as written, leading backslash included.
     *
     * @return void
     */
    public function testRendersTheAttributeNameInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines('DisallowToolingAttribute.inc', [
            12 => ['Attribute "Pure" is an IDE/tooling attribute and is not allowed; use native types and docblocks instead.'],
            18 => ['Attribute "\JetBrains\PhpStorm\Deprecated" is an IDE/tooling attribute and is not allowed; use native types and docblocks instead.'],
            23 => ['Attribute "ArrayShape" is an IDE/tooling attribute and is not allowed; use native types and docblocks instead.'],
        ]);
    }

    /**
     * A configured namespace matches with leading and trailing backslashes.
     *
     * @return void
     */
    public function testMatchesConfiguredNamespacesWithSurroundingBackslashes(): void
    {
        $this->forbiddenNamespaces = ['\JetBrains\PhpStorm\\'];

        $this->assertErrorsOnLines('DisallowToolingAttribute.inc', [12, 18, 23]);
    }

    /**
     * Overlapping configured namespaces report one error per attribute.
     *
     * @return void
     */
    public function testReportsASingleErrorForOverlappingNamespaces(): void
    {
        $this->forbiddenNamespaces = ['JetBrains\PhpStorm', 'JetBrains'];

        $this->assertErrorCodesOnLines('DisallowToolingAttribute.inc', [
            12 => ['Disallowed'],
            18 => ['Disallowed'],
            23 => ['Disallowed'],
        ]);
    }

    /**
     * Names resolve through the file's imports, and a sibling namespace sharing
     * the prefix is not flagged.
     *
     * @return void
     */
    public function testResolvesNamesThroughImportsBeforeMatching(): void
    {
        $this->assertErrorsOnLines('DisallowToolingAttributeResolution.inc', [11, 16, 21, 26]);
    }

    /**
     * Every name in an attribute group is checked; argument lists are skipped.
     *
     * @return void
     */
    public function testScansAttributeGroupsAndSkipsArgumentLists(): void
    {
        $this->assertErrorsOnLines('DisallowToolingAttributeGroups.inc', [10, 20]);
    }

    /**
     * The import map comes from use statements only, including one flush
     * against the previous semicolon.
     *
     * @return void
     */
    public function testBuildsTheImportMapFromUseStatementsOnly(): void
    {
        $this->assertErrorsOnLines('DisallowToolingAttributeUseMap.inc', [17]);
    }

    /**
     * Apply the current test's forbidden-namespace override, if any.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return $this->forbiddenNamespaces === null
            ? []
            : ['forbiddenNamespaces' => $this->forbiddenNamespaces];
    }
}
