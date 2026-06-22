<?php

declare(strict_types = 1);

namespace SineMacula\Tests\NamingConventions;

use PHPUnit\Framework\Attributes\CoversClass;
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
final class BooleanMethodNameSniffTest extends AbstractSniffTestCase
{
    /**
     * Predicate prefixes, third-person verbs, command verbs (built-in and a
     * project-supplied one), @imperative-tagged methods, magic methods, and
     * non-boolean returns are accepted; adjective/participle names - even with
     * a plain (non-@imperative) docblock - are flagged.
     *
     * @return void
     */
    public function testFlagsNonPredicateBooleanMethods(): void
    {
        $this->assertErrorsOnLines('BooleanMethodName.inc', [60, 65, 87]);
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
