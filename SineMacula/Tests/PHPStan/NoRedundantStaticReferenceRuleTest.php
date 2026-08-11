<?php

declare(strict_types = 1);

namespace SineMacula\Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\CodingStandards\PHPStan\Rules\NoRedundantStaticReferenceRule;

/**
 * Tests for the redundant static reference rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @extends \PHPStan\Testing\RuleTestCase<\SineMacula\CodingStandards\PHPStan\Rules\NoRedundantStaticReferenceRule>
 *
 * @internal
 */
#[CoversClass(NoRedundantStaticReferenceRule::class)]
final class NoRedundantStaticReferenceRuleTest extends RuleTestCase
{
    /** The fixture's final class, which reaches through static every way. */
    private const string MAKER = 'App\References\Maker';

    /** The message fragment naming a reach through the static accessor. */
    private const string ACCESSOR = 'static::';

    /**
     * Every way of deferring to static from inside a final class or an enum is
     * flagged: instantiation, a static call, a constant, a static property, the
     * class name and an instanceof. Writing `self` outright is not, nor is any
     * of it inside a non-final or abstract class, nor a trait body a final
     * class uses - a trait cannot know whether its host is final.
     *
     * @return void
     */
    public function testFlagsEveryStaticReferenceInsideAFinalClass(): void
    {
        $this->analyse([__DIR__ . '/data/redundant-static-reference.inc'], [
            [$this->message(self::MAKER, 'new static'), 21],
            [$this->message(self::MAKER, self::ACCESSOR), 26],
            [$this->message(self::MAKER, self::ACCESSOR), 31],
            [$this->message(self::MAKER, self::ACCESSOR), 36],
            [$this->message(self::MAKER, self::ACCESSOR), 41],
            [$this->message(self::MAKER, 'instanceof static'), 46],
            [$this->message('App\References\Suit', self::ACCESSOR), 73],
        ]);
    }

    /**
     * Provide the rule under test.
     *
     * @return \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
     */
    #[\Override]
    protected function getRule(): Rule
    {
        return new NoRedundantStaticReferenceRule;
    }

    /**
     * The expected error message for a needless static reference.
     *
     * @param  string  $class
     * @param  string  $form
     * @return string
     */
    private function message(string $class, string $form): string
    {
        return sprintf(
            '%s uses %s; the class is final, so static is always self.',
            $class,
            $form,
        );
    }
}
