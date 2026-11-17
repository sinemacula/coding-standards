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
     * Flags concrete classes that are neither final nor marked @inheritable.
     *
     * @return void
     */
    public function testFlagsNonFinalConcreteClasses(): void
    {
        $this->assertErrorsOnLines('RequireFinalClass.inc', [13, 33, 48, 57]);
    }

    /**
     * The message names the offending class.
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
