<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbid mutable static state.
 *
 * Static properties are global mutable state: they cannot be `readonly`, so
 * every static property is writable from anywhere. This rule flags every static
 * property declaration. Immutable static data belongs in class constants, and
 * shared behaviour belongs in injected services rather than static state.
 *
 * Intentional, lifecycle-managed statics (e.g. a memo cache flushed at a known
 * boundary such as a request or worker reset) can opt out with a
 * `@managed-static` doc tag on the property or on its declaring class.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassLike>
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
        return ClassLike::class;
    }

    /**
     * Flag each un-annotated static property declared on the class-like node.
     *
     * @param  \PhpParser\Node\Stmt\ClassLike  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->hasManagedStaticTag($node->getDocComment()?->getText())) {
            return [];
        }

        $errors = [];

        foreach ($node->getProperties() as $property) {
            if ($property->isStatic() === false || $this->hasManagedStaticTag($property->getDocComment()?->getText())) {
                continue;
            }

            foreach ($property->props as $declaration) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Static property $%s introduces mutable static state; use instance state or a constant instead.',
                    $declaration->name->toString(),
                ))->identifier('sineMacula.mutableStaticProperty')->line($property->getStartLine())->build();
            }
        }

        return $errors;
    }

    /**
     * Determine whether a doc comment carries the @managed-static opt-out tag.
     *
     * @param  string|null  $docComment
     * @return bool
     */
    private function hasManagedStaticTag(?string $docComment): bool
    {
        return $docComment !== null && preg_match('/@managed-static(?![\w-])/', $docComment) === 1;
    }
}
