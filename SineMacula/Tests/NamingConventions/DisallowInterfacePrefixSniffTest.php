<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\NamingConventions\DisallowInterfacePrefixSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the interface name prefix sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(DisallowInterfacePrefixSniff::class)]
final class DisallowInterfacePrefixSniffTest extends AbstractSniffTestCase
{
    /**
     * Only interfaces using an "I" prefix should be flagged.
     *
     * @return void
     */
    public function testFlagsPrefixedInterfaceNames(): void
    {
        $this->assertErrorsOnLines('DisallowInterfacePrefix.inc', [9]);
    }

    /**
     * The rendered message names the offending interface, proving the name is
     * passed through to the report.
     *
     * @return void
     */
    public function testReportsTheOffendingInterfaceNameInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines('DisallowInterfacePrefix.inc', [
            9 => ['Interface "IUserRepository" must not use an "I" prefix.'],
        ]);
    }
}
