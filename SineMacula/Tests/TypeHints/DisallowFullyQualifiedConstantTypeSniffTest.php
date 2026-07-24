<?php

declare(strict_types = 1);

namespace SineMacula\Tests\TypeHints;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;
use SineMacula\Sniffs\TypeHints\DisallowFullyQualifiedConstantTypeSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the fully qualified constant type sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(DisallowFullyQualifiedConstantTypeSniff::class)]
#[CoversTrait(ResolvesQualifiedNames::class)]
final class DisallowFullyQualifiedConstantTypeSniffTest extends AbstractSniffTestCase
{
    /**
     * A namespaced fully qualified name in a constant type is flagged, once per
     * name: single, both members of a union or intersection, and the inner name
     * of a nullable type. Global fully qualified names (`\DateTimeInterface`,
     * `\Throwable`), imported short names, partially qualified names, scalar
     * types and untyped constants are not flagged, and a fully qualified name
     * in the value rather than the type is left alone.
     *
     * @return void
     */
    public function testFlagsNamespacedFullyQualifiedConstantTypes(): void
    {
        $this->assertErrorsOnLines('DisallowFullyQualifiedConstantType.inc', [9, 23, 25, 27, 29]);
    }

    /**
     * Union and intersection types report one error per namespaced name, while
     * a global name alongside a namespaced one in a union reports only the
     * namespaced name.
     *
     * @return void
     */
    public function testReportsEachNamespacedNameInCompoundTypes(): void
    {
        $this->assertErrorCodesOnLines('DisallowFullyQualifiedConstantType.inc', [
            9  => ['FullyQualified'],
            23 => ['FullyQualified', 'FullyQualified'],
            25 => ['FullyQualified', 'FullyQualified'],
            27 => ['FullyQualified'],
            29 => ['FullyQualified'],
        ]);
    }

    /**
     * The rendered error names the offending fully qualified name so the report
     * points at the reference that must be imported.
     *
     * @return void
     */
    public function testReportsTheOffendingName(): void
    {
        $this->assertErrorMessagesOnLines('DisallowFullyQualifiedConstantType.inc', [
            9  => ['Class constant type "\App\Contracts\ErrorCode" must be imported and referenced by its short name.'],
            23 => [
                'Class constant type "\App\Types\A" must be imported and referenced by its short name.',
                'Class constant type "\App\Types\B" must be imported and referenced by its short name.',
            ],
            25 => [
                'Class constant type "\App\Types\C" must be imported and referenced by its short name.',
                'Class constant type "\App\Types\D" must be imported and referenced by its short name.',
            ],
            27 => ['Class constant type "\App\Types\N" must be imported and referenced by its short name.'],
            29 => ['Class constant type "\App\Types\E" must be imported and referenced by its short name.'],
        ]);
    }

    /**
     * A namespaced name is flagged whatever its depth (a two-segment name and a
     * deep one alike) and in every object structure that carries constants -
     * class, interface, trait and enum - past leading modifiers, and once for a
     * type shared by several constants in one declaration. Each namespaced name
     * in a disjunctive-normal-form type is flagged. A partially qualified name
     * (no leading separator), a `namespace`-relative name and a nullable global
     * name are never flagged, whichever phpcs tokenises them.
     *
     * @return void
     */
    public function testFlagsNamespacedNamesAcrossDepthsAndStructures(): void
    {
        $this->assertErrorsOnLines('DisallowFullyQualifiedConstantTypeEdges.inc', [7, 11, 13, 18, 23, 28, 37, 39]);
    }
}
