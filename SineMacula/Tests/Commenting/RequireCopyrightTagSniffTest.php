<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
use SineMacula\Sniffs\Commenting\RequireCopyrightTagSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the copyright tag sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireCopyrightTagSniff::class)]
#[CoversTrait(ResolvesDocComment::class)]
final class RequireCopyrightTagSniffTest extends AbstractSniffTestCase
{
    /**
     * A docblock without @copyright is flagged; one with it, and a structure
     * with no docblock at all (left to the class-comment sniff), are not. The
     * docblock is still found when an attribute sits between it and the class,
     * so an attribute-tagged class missing @copyright is flagged.
     *
     * @return void
     */
    public function testFlagsDocblocksMissingCopyright(): void
    {
        $this->assertErrorsOnLines('RequireCopyrightTag.inc', [20, 45]);
    }

    /**
     * The docblock is found past an abstract modifier, so an abstract class
     * missing @copyright is flagged; the tag is matched case-insensitively, so
     * a mixed-case @Copyright satisfies the sniff.
     *
     * @return void
     */
    public function testLooksPastAbstractAndMatchesTagCaseInsensitively(): void
    {
        $this->assertErrorsOnLines('RequireCopyrightTagEdges.inc', [10]);
    }

    /**
     * The reported message names the offending structure.
     *
     * @return void
     */
    public function testReportsStructureNameInMessage(): void
    {
        $this->assertErrorMessagesOnLines('RequireCopyrightTag.inc', [
            20 => ['Doc comment for "NoCopyright" must include an @copyright tag.'],
            45 => ['Doc comment for "NoCopyrightWithAttribute" must include an @copyright tag.'],
        ]);
    }
}
