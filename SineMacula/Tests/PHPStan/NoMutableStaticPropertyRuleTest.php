<?php

declare(strict_types = 1);

namespace SineMacula\Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\CodingStandards\PHPStan\Rules\NoMutableStaticPropertyRule;

/**
 * Tests for the mutable static property rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @extends \PHPStan\Testing\RuleTestCase<\SineMacula\CodingStandards\PHPStan\Rules\NoMutableStaticPropertyRule>
 *
 * @internal
 */
#[CoversClass(NoMutableStaticPropertyRule::class)]
final class NoMutableStaticPropertyRuleTest extends RuleTestCase
{
    /**
     * Static properties are flagged; instance properties and constants are not.
     *
     * @return void
     */
    public function testFlagsStaticProperties(): void
    {
        $this->analyse([__DIR__ . '/data/mutable-static.inc'], [
            [
                'Static property $cache introduces mutable static state; use instance state or a constant instead.',
                7,
            ],
        ]);
    }

    /**
     * Provide the rule under test.
     *
     * @return \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Property>
     */
    #[\Override]
    protected function getRule(): Rule
    {
        return new NoMutableStaticPropertyRule;
    }
}
