<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesDocComment;
use SineMacula\Sniffs\NamingConventions\BooleanMethodNameSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the boolean method name sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(BooleanMethodNameSniff::class)]
#[CoversTrait(ResolvesDocComment::class)]
final class BooleanMethodNameSniffTest extends AbstractSniffTestCase
{
    /**
     * Predicate prefixes, third-person (-s) and past-tense (-ed) verbs,
     * idiomatic predicates (successful), command verbs (built-in and a
     * project-supplied one), @imperative-tagged and magic methods, and
     * non-boolean returns are accepted; the @imperative opt-out is
     * case-insensitive and still holds when an attribute (single, stacked or
     * inline) sits between the docblock and the signature, or when the docblock
     * closes flush against the function keyword or the attribute. An adjective
     * name is flagged whether the method carries no docblock, a plain docblock,
     * a docblock hidden behind an attribute, or a docblock whose only tag is
     * not the opt-out; a nullable bool return and a PascalCase name are flagged
     * too.
     *
     * @return void
     */
    public function testFlagsNonPredicateBooleanMethods(): void
    {
        $this->assertErrorsOnLines('BooleanMethodName.inc', [87, 134, 139, 144, 149, 159]);
    }

    /**
     * The rendered message names the offending method, proving the name is
     * passed through to the report.
     *
     * @return void
     */
    public function testReportsTheOffendingMethodNameInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines('BooleanMethodName.inc', [
            87  => ['Boolean method "ready" should read as a predicate (is/has/can/...).'],
            134 => ['Boolean method "available" should read as a predicate (is/has/can/...).'],
            139 => ['Boolean method "pending" should read as a predicate (is/has/can/...).'],
            144 => ['Boolean method "HasErrors" should read as a predicate (is/has/can/...).'],
            149 => ['Boolean method "operational" should read as a predicate (is/has/can/...).'],
            159 => ['Boolean method "launch" should read as a predicate (is/has/can/...).'],
        ]);
    }

    /**
     * Extend the built-in command verbs with a project-supplied one, mirroring
     * a consumer ruleset's property override with extend set.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return ['commandVerbs' => [...(new BooleanMethodNameSniff)->commandVerbs, 'frobnicate']];
    }
}
