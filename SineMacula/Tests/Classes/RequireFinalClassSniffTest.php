<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Classes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
use SineMacula\Sniffs\Classes\RequireFinalClassSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the final class sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireFinalClassSniff::class)]
#[CoversTrait(ResolvesDocComment::class)]
final class RequireFinalClassSniffTest extends AbstractSniffTestCase
{
    /**
     * Only concrete classes that are neither final nor marked @inheritable
     * should be flagged. The @inheritable opt-out still holds when an attribute
     * sits between the docblock and the class; a concrete class behind an
     * attribute with no opt-out is still flagged. A docblock carrying an
     * unrelated tag does not opt out, while the opt-out tag itself matches
     * case-insensitively.
     *
     * @return void
     */
    public function testFlagsNonFinalConcreteClasses(): void
    {
        $this->assertErrorsOnLines('RequireFinalClass.inc', [13, 33, 48, 57]);
    }

    /**
     * The rendered error names the offending class so the report points at the
     * declaration that must change.
     *
     * @return void
     */
    public function testReportsTheOffendingClassName(): void
    {
        $this->assertErrorMessagesOnLines('RequireFinalClass.inc', [
            13 => ['Class "Plain" must be declared final or abstract (or marked @inheritable).'],
            33 => ['Class "Documented" must be declared final or abstract (or marked @inheritable).'],
            48 => ['Class "PlainWithAttribute" must be declared final or abstract (or marked @inheritable).'],
            57 => ['Class "TaggedButConcrete" must be declared final or abstract (or marked @inheritable).'],
        ]);
    }
}
