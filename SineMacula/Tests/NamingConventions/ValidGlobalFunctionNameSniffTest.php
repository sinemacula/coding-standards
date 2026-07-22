<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\NamingConventions\ValidGlobalFunctionNameSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the global function naming sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(ValidGlobalFunctionNameSniff::class)]
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

    /**
     * The rendered message names the offending function, proving the name is
     * passed through to the report.
     *
     * @return void
     */
    public function testReportsTheOffendingFunctionNameInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines('ValidGlobalFunctionName.inc', [
            9  => ['Global function "doThing" must be declared in snake_case.'],
            13 => ['Global function "DoThing" must be declared in snake_case.'],
        ]);
    }
}
