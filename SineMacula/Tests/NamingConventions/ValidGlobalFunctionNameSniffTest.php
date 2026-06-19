<?php

namespace SineMacula\Tests\NamingConventions;

use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the global function naming sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class ValidGlobalFunctionNameSniffTest extends AbstractSniffTestCase
{
    /**
     * Only global functions that are not snake_case should be flagged.
     *
     * @return void
     */
    public function testFlagsGlobalFunctionsNotInSnakeCase(): void
    {
        $this->assertErrorsOnLines('ValidGlobalFunctionName.inc', [9, 13]);
    }
}
