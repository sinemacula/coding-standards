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
     * The rendered message names the attribute exactly as written in the code,
     * including any leading backslash.
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
     * A configured namespace still matches when written with leading and
     * trailing backslashes, so sloppy configuration values behave the same as
     * the canonical form.
     *
     * @return void
     */
    public function testMatchesConfiguredNamespacesWithSurroundingBackslashes(): void
    {
        $this->forbiddenNamespaces = ['\JetBrains\PhpStorm\\'];

        $this->assertErrorsOnLines('DisallowToolingAttribute.inc', [12, 18, 23]);
    }

    /**
     * Overlapping configured namespaces that both match the same attribute
     * report a single error, not one per entry.
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
     * Names are resolved through the file's imports: a namespace-prefix alias,
     * a class alias and a leading-backslash import all resolve into the
     * forbidden namespace, the namespace itself is flagged, and a sibling
     * namespace that merely shares the prefix as a substring is not.
     *
     * @return void
     */
    public function testResolvesNamesThroughImportsBeforeMatching(): void
    {
        $this->assertErrorsOnLines('DisallowToolingAttributeResolution.inc', [11, 16, 21, 26]);
    }

    /**
     * Every name in a grouped attribute is checked, while tokens inside an
     * attribute's argument list are never mistaken for attribute names.
     *
     * @return void
     */
    public function testScansAttributeGroupsAndSkipsArgumentLists(): void
    {
        $this->assertErrorsOnLines('DisallowToolingAttributeGroups.inc', [10, 20]);
    }

    /**
     * The import map is built from the use statements alone: a name imported
     * from a safe namespace stays safe even when a forbidden class is
     * referenced elsewhere in the file, and an import directly adjacent to the
     * previous statement's semicolon is still parsed on its own.
     *
     * @return void
     */
    public function testBuildsTheImportMapFromUseStatementsOnly(): void
    {
        $this->assertErrorsOnLines('DisallowToolingAttributeUseMap.inc', [17]);
    }

    /**
     * Apply the forbidden-namespace override chosen by the current test, if
     * any.
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
