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
     * Undocumented cases are flagged only when a sibling case is documented.
     *
     * @return void
     */
    public function testFlagsInconsistentlyDocumentedEnumCases(): void
    {
        $this->assertErrorsOnLines('ConsistentEnumCaseComments.inc', [10, 28]);
    }

    /**
     * A case directly after the opening brace is still counted.
     *
     * @return void
     */
    public function testCountsCaseImmediatelyAfterOpeningBrace(): void
    {
        $this->assertErrorsOnLines('ConsistentEnumCaseCommentsEdges.inc', [6]);
    }
}
