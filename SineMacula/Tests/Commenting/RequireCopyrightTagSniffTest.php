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
     * A docblock without @copyright is flagged; a missing docblock is not.
     *
     * @return void
     */
    public function testFlagsDocblocksMissingCopyright(): void
    {
        $this->assertErrorsOnLines('RequireCopyrightTag.inc', [20, 45]);
    }

    /**
     * The docblock is found past `abstract`, and the tag matches
     * case-insensitively.
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
