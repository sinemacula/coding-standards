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
     * Un-annotated static properties are flagged, including ones with a
     * non-managed docblock. A property or class carrying the opt-out tag,
     * instance properties, and constants are not.
     *
     * @return void
     */
    public function testFlagsStaticProperties(): void
    {
        $this->analyse([__DIR__ . '/data/mutable-static.inc'], [
            [$this->message('cache'), 7],
            [$this->message('documented'), 10],
        ]);
    }

    /**
     * Provide the rule under test.
     *
     * @return \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassLike>
     */
    #[\Override]
    protected function getRule(): Rule
    {
        return new NoMutableStaticPropertyRule;
    }

    /**
     * The expected error message for a flagged static property.
     *
     * @param  string  $name
     * @return string
     */
    private function message(string $name): string
    {
        return sprintf(
            'Static property $%s introduces mutable static state; use instance state or a constant instead.',
            $name,
        );
    }
}
