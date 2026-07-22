<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
use SineMacula\Sniffs\Commenting\ConsistentEnumCaseCommentsSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the enum case comment consistency sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(ConsistentEnumCaseCommentsSniff::class)]
#[CoversTrait(ResolvesDocComment::class)]
final class ConsistentEnumCaseCommentsSniffTest extends AbstractSniffTestCase
{
    /**
     * When some but not all cases are documented, the undocumented ones are
     * flagged; a fully undocumented enum is left alone. A case documented
     * behind an attribute still counts as documented, so its plain undocumented
     * sibling is flagged.
     *
     * @return void
     */
    public function testFlagsInconsistentlyDocumentedEnumCases(): void
    {
        $this->assertErrorsOnLines('ConsistentEnumCaseComments.inc', [10, 28]);
    }

    /**
     * A case declared immediately after the opening brace is still counted, so
     * it is flagged when a later case is documented.
     *
     * @return void
     */
    public function testCountsCaseImmediatelyAfterOpeningBrace(): void
    {
        $this->assertErrorsOnLines('ConsistentEnumCaseCommentsEdges.inc', [6]);
    }
}
