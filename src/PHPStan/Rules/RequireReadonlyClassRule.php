<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Require a final, parentless class whose every property is readonly to be
 * declared `readonly`.
 *
 * Trait-provided properties count. Enums and test classes are exempt.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\InClassNode>
 */
final class RequireReadonlyClassRule implements Rule
{
    /**
     * The node type this rule inspects.
     *
     * @return string
     */
    #[\Override]
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * Flag a final leaf class whose every property is readonly.
     *
     * @param  \PHPStan\Node\InClassNode  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $reflection = $node->getClassReflection()->getNativeReflection();

        if ($this->isExempt($reflection, $scope) || $this->hasOnlyReadonlyProperties($reflection) === false) {
            return [];
        }

        return [$this->error($reflection->getName())];
    }

    /**
     * Whether the class is outside the rule's scope.
     *
     * @param  \ReflectionClass<object>  $reflection
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return bool
     */
    private function isExempt(\ReflectionClass $reflection, Scope $scope): bool
    {
        // An enum has readonly properties but cannot be declared readonly.
        if (
            $reflection->isEnum()
            || $reflection->isFinal() === false
            || $reflection->isReadOnly()
            || $reflection->getParentClass() !== false
        ) {
            return true;
        }

        return $this->isTestClass($reflection, $scope);
    }

    /**
     * Whether the class has at least one property and all are readonly.
     *
     * @param  \ReflectionClass<object>  $reflection
     * @return bool
     */
    private function hasOnlyReadonlyProperties(\ReflectionClass $reflection): bool
    {
        $properties = $reflection->getProperties();

        if ($properties === []) {
            return false;
        }

        foreach ($properties as $property) {
            // A static property is never readonly, so this excludes it too.
            if ($property->isReadOnly() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the class is a test class.
     *
     * A `*TestCase` subclass has a parent, so it is already exempt.
     *
     * @param  \ReflectionClass<object>  $reflection
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return bool
     */
    private function isTestClass(\ReflectionClass $reflection, Scope $scope): bool
    {
        return str_ends_with($reflection->getShortName(), 'Test')
            || str_contains(str_replace('\\', '/', $scope->getFile()), '/tests/');
    }

    /**
     * Build the error for a class that should be declared readonly.
     *
     * @param  string  $class
     * @return \PHPStan\Rules\RuleError
     */
    private function error(string $class): RuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Class %s must be declared readonly; all of its properties are readonly.',
            $class,
        ))
            ->identifier('sineMacula.readonlyClass')
            ->build();
    }
}
