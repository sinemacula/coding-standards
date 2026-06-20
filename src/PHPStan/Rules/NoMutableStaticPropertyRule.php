<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbid mutable static state.
 *
 * Static properties are global mutable state: they cannot be `readonly`, so
 * every static property is writable from anywhere. This rule flags every
 * static property declaration. Immutable static data belongs in class
 * constants, and shared behaviour belongs in injected services rather than
 * static state.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Property>
 */
final class NoMutableStaticPropertyRule implements Rule
{
    /**
     * The node type this rule inspects.
     *
     * @return string
     */
    #[\Override]
    public function getNodeType(): string
    {
        return Property::class;
    }

    /**
     * Flag each static property declared on the node.
     *
     * @param  \PhpParser\Node\Stmt\Property  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isStatic() === false) {
            return [];
        }

        $errors = [];

        foreach ($node->props as $property) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Static property $%s introduces mutable static state; use instance state or a constant instead.',
                $property->name->toString(),
            ))->identifier('sineMacula.mutableStaticProperty')->build();
        }

        return $errors;
    }
}
