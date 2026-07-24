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
 * Require a readonly class where every property is readonly.
 *
 * A final class that holds at least one property, whose every property -
 * declared, promoted or trait-provided - is `readonly` and none static, and
 * that extends nothing, must be declared a `readonly` class rather than
 * repeating the modifier on each property. Only final leaf classes qualify: a
 * readonly class forces the modifier onto every descendant, so promoting a base
 * would silently constrain its subclasses. A class that extends anything is
 * left alone - a non-readonly class can only extend a non-readonly parent,
 * which a readonly class may not do - as is a class carrying a mutable or
 * static property, a class using a trait that declares one, an already-readonly
 * class, and interfaces, traits, enums, anonymous classes and test classes.
 * Seeing through traits to the properties they contribute needs the
 * whole-program type graph, which is why this is a PHPStan rule rather than a
 * single-file sniff.
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
     * Whether the class is outside the rule's scope: not a concrete final leaf
     * class, already readonly, extends a parent, or is a test class.
     *
     * @param  \ReflectionClass<object>  $reflection
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return bool
     */
    private function isExempt(\ReflectionClass $reflection, Scope $scope): bool
    {
        // An enum is final and reports its implicit `$name`/`$value` as
        // readonly properties, but cannot itself be declared readonly, so it is
        // excluded explicitly. Interfaces, traits, anonymous classes and
        // non-final classes are all non-final and fall out with the finality
        // check.
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
     * Whether the class declares at least one property and every one of them,
     * including those contributed by traits, is readonly and non-static.
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
            // A static property can never be readonly, so the readonly check
            // also excludes a class that declares one.
            if ($property->isReadOnly() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the class is a test class, where holding readonly state is not
     * the concern the rule guards. A class extending a *TestCase carries a
     * parent and so is already out of scope, leaving the name and directory to
     * check here.
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
