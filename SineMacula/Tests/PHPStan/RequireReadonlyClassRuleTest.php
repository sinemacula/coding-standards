<?php

declare(strict_types = 1);

namespace SineMacula\Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\CodingStandards\PHPStan\Rules\RequireReadonlyClassRule;

/**
 * Tests for the readonly class rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @extends \PHPStan\Testing\RuleTestCase<\SineMacula\CodingStandards\PHPStan\Rules\RequireReadonlyClassRule>
 *
 * @internal
 */
#[CoversClass(RequireReadonlyClassRule::class)]
final class RequireReadonlyClassRuleTest extends RuleTestCase
{
    /**
     * Flags final classes whose properties, traits included, are all readonly.
     *
     * @return void
     */
    public function testFlagsFinalClassesWithOnlyReadonlyProperties(): void
    {
        $this->analyse([__DIR__ . '/data/readonly-class.inc'], [
            [$this->message('App\Money'), 19],
            [$this->message('App\WithReadonlyTrait'), 26],
        ]);
    }

    /**
     * A final all-readonly class under a tests/ directory is exempt.
     *
     * @return void
     */
    public function testExemptsClassesUnderTestsDirectory(): void
    {
        $this->analyse([__DIR__ . '/data/tests/readonly-class-helper.inc'], []);
    }

    /**
     * Provide the rule under test.
     *
     * @return \PHPStan\Rules\Rule<\PHPStan\Node\InClassNode>
     */
    #[\Override]
    protected function getRule(): Rule
    {
        return new RequireReadonlyClassRule;
    }

    /**
     * The expected error message for a class that should be declared readonly.
     *
     * @param  string  $class
     * @return string
     */
    private function message(string $class): string
    {
        return sprintf(
            'Class %s must be declared readonly; all of its properties are readonly.',
            $class,
        );
    }
}
