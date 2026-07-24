<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Classes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\DetectsTestClasses;
use SineMacula\Sniffs\Classes\RequireReadonlyClassSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the readonly class sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireReadonlyClassSniff::class)]
#[CoversTrait(DetectsTestClasses::class)]
final class RequireReadonlyClassSniffTest extends AbstractSniffTestCase
{
    /**
     * A concrete class is flagged when every property is readonly, whether the
     * properties are declared (including several in one statement), promoted
     * (including behind a differently cased constructor name and beside a plain
     * non-promoted parameter), a mix of the two, or non-public, and constants
     * do not count as properties; a class extending an ordinary parent or
     * implementing an interface is flagged like any other. A class with a
     * mutable declared or promoted property, a static property, no properties
     * at all, or that is already readonly, abstract, extends an ignored parent,
     * or is a test class, is not flagged.
     *
     * @return void
     */
    public function testFlagsClassesWhereEveryPropertyIsReadonly(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyClass.inc', [5, 12, 21, 31, 38, 106, 111, 119, 128, 133]);
    }

    /**
     * The rendered error names the offending class so the report points at the
     * declaration that must gain the modifier.
     *
     * @return void
     */
    public function testReportsTheOffendingClassName(): void
    {
        $this->assertErrorMessagesOnLines('RequireReadonlyClass.inc', [
            5   => ['Class "ValueObject" must be declared readonly; all of its properties are readonly.'],
            12  => ['Class "Money" must be declared readonly; all of its properties are readonly.'],
            21  => ['Class "Combined" must be declared readonly; all of its properties are readonly.'],
            31  => ['Class "PrivateState" must be declared readonly; all of its properties are readonly.'],
            38  => ['Class "WithConstants" must be declared readonly; all of its properties are readonly.'],
            106 => ['Class "Derived" must be declared readonly; all of its properties are readonly.'],
            111 => ['Class "MixedCaseConstructor" must be declared readonly; all of its properties are readonly.'],
            119 => ['Class "PlainAndPromoted" must be declared readonly; all of its properties are readonly.'],
            128 => ['Class "Grouped" must be declared readonly; all of its properties are readonly.'],
            133 => ['Class "Coordinates" must be declared readonly; all of its properties are readonly.'],
        ]);
    }

    /**
     * Properties are attributed to their innermost enclosing class: a class
     * nested in a method is judged on its own properties, and the outer class
     * is judged only on the properties it declares directly, ignoring locals
     * and the members of the nested class.
     *
     * @return void
     */
    public function testAttributesPropertiesToTheInnermostClass(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyClassNested.inc', [5, 31]);
    }

    /**
     * A test double under a tests/ directory is exempt whatever its name.
     *
     * @return void
     */
    public function testExemptsTestDoublesUnderTestsDirectory(): void
    {
        $this->assertErrorsOnLines('tests/ReadonlyClassTestDouble.inc', []);
    }

    /**
     * Exempt classes extending the fixture's mutable entity base.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return ['ignoredParentClasses' => ['Model']];
    }
}
