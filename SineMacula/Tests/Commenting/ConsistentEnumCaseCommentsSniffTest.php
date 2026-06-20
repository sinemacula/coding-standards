<?php

namespace SineMacula\Tests\Commenting;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the enum case comment consistency sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class ConsistentEnumCaseCommentsSniffTest extends AbstractSniffTestCase
{
    /**
     * When some but not all cases are documented, the undocumented ones are
     * flagged; a fully undocumented enum is left alone.
     *
     * @return void
     */
    public function testFlagsInconsistentlyDocumentedEnumCases(): void
    {
        $this->assertErrorsOnLines('ConsistentEnumCaseComments.inc', [10]);
    }
}
