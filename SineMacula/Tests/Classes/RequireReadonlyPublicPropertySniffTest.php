<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Classes;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Classes\RequireReadonlyPublicPropertySniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the readonly public property sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireReadonlyPublicPropertySniff::class)]
final class RequireReadonlyPublicPropertySniffTest extends AbstractSniffTestCase
{
    /**
     * Public mutable properties (declared and promoted) are flagged; readonly,
     * non-public and static properties, readonly/ignored-parent classes, and
     * test classes (named *Test or extending *TestCase) are not.
     *
     * @return void
     */
    public function testFlagsMutablePublicProperties(): void
    {
        $this->assertErrorsOnLines('RequireReadonlyPublicProperty.inc', [7, 18]);
    }

    /**
     * Test doubles under a tests/ directory are exempt whatever their name.
     *
     * @return void
     */
    public function testExemptsTestDoublesUnderTestsDirectory(): void
    {
        $this->assertErrorsOnLines('tests/ReadonlyTestDouble.inc', []);
    }

    /**
     * Exempt classes extending the fixture's mutable entity bases.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return ['ignoredParentClasses' => ['Model', 'Base']];
    }
}
