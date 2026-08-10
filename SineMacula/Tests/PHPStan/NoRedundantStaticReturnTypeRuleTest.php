<?php

declare(strict_types = 1);

namespace SineMacula\Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\CodingStandards\PHPStan\Rules\NoRedundantStaticReturnTypeRule;

/**
 * Tests for the redundant static return type rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @extends \PHPStan\Testing\RuleTestCase<\SineMacula\CodingStandards\PHPStan\Rules\NoRedundantStaticReturnTypeRule>
 *
 * @internal
 */
#[CoversClass(NoRedundantStaticReturnTypeRule::class)]
final class NoRedundantStaticReturnTypeRuleTest extends RuleTestCase
{
    /** The fixture's final class covering every way a return can say static. */
    private const string FACTORY = 'App\Statics\Factory';

    /** The fixture's final class overriding a parent that never says static. */
    private const string COPIER = 'App\Statics\Copier';

    /** The message fragment naming a static written as a native return type. */
    private const string NATIVE = 'return type';

    /** The message fragment naming a static written in a return tag. */
    private const string DOCUMENTED = '@return tag';

    /**
     * A method of a final class or an enum returning static is flagged, whether
     * the type is declared natively (plainly, nullably or in a union), written
     * in a return tag, or both, and whatever case the keyword carries. So is an
     * override of a parent method that declares no return type at all. A method
     * whose return type an ancestor pins to static is not, nor is one whose
     * only static ancestor declaration is private, a method of a non-final or
     * abstract class, a trait method a final class uses, or a return tag whose
     * description merely mentions the word.
     *
     * @return void
     */
    public function testFlagsStaticReturnsThatCanOnlyMeanSelf(): void
    {
        $this->analyse([__DIR__ . '/data/redundant-static-return.inc'], [
            [$this->message(self::FACTORY, 'imported', self::NATIVE . ' and its ' . self::DOCUMENTED), 68],
            [$this->message(self::FACTORY, 'maybe', self::NATIVE), 73],
            [$this->message(self::FACTORY, 'either', self::NATIVE), 78],
            [$this->message(self::FACTORY, 'loud', self::NATIVE), 83],
            [$this->message(self::FACTORY, 'many', self::DOCUMENTED), 96],
            [$this->message(self::FACTORY, 'shouted', self::DOCUMENTED), 104],
            [$this->message(self::FACTORY, 'tagged', self::DOCUMENTED), 112],
            [$this->message('App\Statics\Locked', 'hidden', self::NATIVE), 185],
            [$this->message(self::COPIER, 'copy', self::NATIVE), 193],
            [$this->message(self::COPIER, 'untyped', self::NATIVE), 198],
            [$this->message(self::COPIER, 'widened', self::NATIVE), 203],
            [$this->message('App\Statics\Suit', 'itself', self::NATIVE), 218],
        ]);
    }

    /**
     * Provide the rule under test.
     *
     * @return \PHPStan\Rules\Rule<\PHPStan\Node\InClassMethodNode>
     */
    #[\Override]
    protected function getRule(): Rule
    {
        return new NoRedundantStaticReturnTypeRule;
    }

    /**
     * The expected error message for a method returning static needlessly.
     *
     * @param  string  $class
     * @param  string  $method
     * @param  string  $surfaces
     * @return string
     */
    private function message(string $class, string $method, string $surfaces): string
    {
        return sprintf(
            'Method %s::%s() declares static in its %s; the class is final, so static is always self.',
            $class,
            $method,
            $surfaces,
        );
    }
}
