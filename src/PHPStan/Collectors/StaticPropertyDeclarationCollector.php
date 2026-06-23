<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\PHPStan\Collectors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collect static property declarations eligible for the mutable-static rule.
 *
 * Records every static property declaration that is not opted out with a
 * `@managed-static` doc tag (on the property or its declaring class). Test
 * classes and anonymous classes are skipped entirely - mutable statics there
 * track state across assertions, not production global state. Each record
 * carries the "Class::property" key, the property name and the declaration
 * line, so the rule can flag the ones that are also written.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @implements \PHPStan\Collectors\Collector<\PhpParser\Node\Stmt\ClassLike, array<int, array<string, int|string>>>
 */
final class StaticPropertyDeclarationCollector implements Collector
{
    /**
     * The node type this collector inspects.
     *
     * @return string
     */
    #[\Override]
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /**
     * Collect the non-managed static property declarations on the class.
     *
     * @param  \PhpParser\Node\Stmt\ClassLike  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return array<int, array<string, int|string>>|null
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): ?array
    {
        if ($node->namespacedName === null || $this->isTestClass($node, $scope)) {
            return null;
        }

        $class        = $node->namespacedName->toString();
        $classManaged = $this->hasManagedStaticTag($node->getDocComment()?->getText());
        $records      = [];

        foreach ($node->getProperties() as $property) {
            $records = array_merge($records, $this->collectFromProperty($property, $class, $classManaged));
        }

        return $records === [] ? null : $records;
    }

    /**
     * Whether the class is a test class, where mutable statics track state
     * across assertions rather than introduce production global state.
     *
     * @param  \PhpParser\Node\Stmt\ClassLike  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return bool
     */
    private function isTestClass(ClassLike $node, Scope $scope): bool
    {
        return str_ends_with($node->name?->toString() ?? '', 'Test')
            || str_contains(str_replace('\\', '/', $scope->getFile()), '/tests/')
            || ($node instanceof Class_ && str_ends_with($node->extends?->getLast() ?? '', 'TestCase'));
    }

    /**
     * Collect the records for a single property declaration node.
     *
     * @param  \PhpParser\Node\Stmt\Property  $property
     * @param  string  $class
     * @param  bool  $classManaged
     * @return array<int, array<string, int|string>>
     */
    private function collectFromProperty(Property $property, string $class, bool $classManaged): array
    {
        if ($property->isStatic() === false) {
            return [];
        }

        if ($classManaged || $this->hasManagedStaticTag($property->getDocComment()?->getText())) {
            return [];
        }

        $records = [];

        foreach ($property->props as $declaration) {
            $name      = $declaration->name->toString();
            $records[] = ['key' => $class . '::' . $name, 'name' => $name, 'line' => $property->getStartLine()];
        }

        return $records;
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
