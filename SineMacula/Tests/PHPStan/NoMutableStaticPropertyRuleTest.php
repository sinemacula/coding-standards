<?php

declare(strict_types = 1);

namespace SineMacula\Tests\PHPStan;

use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\CodingStandards\PHPStan\Collectors\StaticPropertyDeclarationCollector;
use SineMacula\CodingStandards\PHPStan\Collectors\StaticPropertyWriteCollector;
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
#[CoversClass(StaticPropertyWriteCollector::class)]
#[CoversClass(StaticPropertyDeclarationCollector::class)]
final class NoMutableStaticPropertyRuleTest extends RuleTestCase
{
    /**
     * Only static properties written at runtime are flagged; read-only config,
     * a @managed-static opt-out, instance properties and constants are not.
     *
     * @return void
     */
    public function testFlagsWrittenStaticProperties(): void
    {
        $this->analyse([__DIR__ . '/data/mutable-static.inc'], [
            [$this->message('count'), 7],
            [$this->message('shared'), 14],
        ]);
    }

    /**
     * The rule flags a declaration only when a matching write was collected.
     *
     * @return void
     */
    public function testCorrelatesWritesToDeclarations(): void
    {
        // A synthetic node is the only way to unit-test the rule directly.
        // @phpstan-ignore phpstanApi.constructor
        $node = new CollectedDataNode([
            __FILE__ => [
                StaticPropertyWriteCollector::class       => ['App\Sample::$written'],
                StaticPropertyDeclarationCollector::class => [[
                    ['key' => 'App\Sample::$written', 'name' => 'written', 'line' => 5],
                    ['key' => 'App\Sample::$config', 'name' => 'config', 'line' => 8],
                ]],
            ],
        ], false);

        $rule   = new NoMutableStaticPropertyRule;
        $errors = $rule->processNode($node, self::createStub(Scope::class));

        self::assertSame(CollectedDataNode::class, $rule->getNodeType());
        self::assertCount(1, $errors);
        self::assertStringContainsString('$written', $errors[0]->getMessage());
    }

    /**
     * Provide the rule under test.
     *
     * @return \PHPStan\Rules\Rule<\PHPStan\Node\CollectedDataNode>
     */
    #[\Override]
    protected function getRule(): Rule
    {
        return new NoMutableStaticPropertyRule;
    }

    /**
     * Provide the collectors the rule correlates.
     *
     * @return array<int, \PHPStan\Collectors\Collector<\PhpParser\Node, mixed>>
     */
    #[\Override]
    protected function getCollectors(): array
    {
        return [
            new StaticPropertyWriteCollector,
            new StaticPropertyDeclarationCollector,
        ];
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
