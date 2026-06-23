<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use SineMacula\CodingStandards\PHPStan\Collectors\StaticPropertyDeclarationCollector;
use SineMacula\CodingStandards\PHPStan\Collectors\StaticPropertyWriteCollector;

/**
 * Forbid mutable static state.
 *
 * Static properties are global mutable state. This rule flags a static property
 * only when it is actually written somewhere in the analysed program - an
 * assignment, compound assignment or increment/decrement. A static that is only
 * declared and read is configuration (like `$fillable`), not mutable state, and
 * is left alone. Deliberately-mutated statics (e.g. a memo cache flushed at a
 * known boundary) opt out with a `@managed-static` doc tag on the property or
 * its declaring class. Test classes are exempt - mutable statics there track
 * state across assertions, not production global state.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @phpstan-type StaticPropertyDeclaration array<string, int|string>
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\CollectedDataNode>
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
        return CollectedDataNode::class;
    }

    /**
     * Flag each declared static property that is also written at runtime.
     *
     * @param  \PHPStan\Node\CollectedDataNode  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $writes = $this->writtenKeys($node);
        $errors = [];

        foreach ($this->declarations($node) as $declaration) {
            if (isset($writes[$declaration['key']]) === false) {
                continue;
            }

            $errors[] = $this->error($declaration);
        }

        return $errors;
    }

    /**
     * The set of "Class::property" keys written anywhere in the program.
     *
     * @param  \PHPStan\Node\CollectedDataNode  $node
     * @return array<string, true>
     */
    private function writtenKeys(CollectedDataNode $node): array
    {
        $keys = [];

        foreach ($node->get(StaticPropertyWriteCollector::class) as $fileWrites) {
            foreach ($fileWrites as $key) {
                $keys[$key] = true;
            }
        }

        return $keys;
    }

    /**
     * Every collected static property declaration, flattened with its file.
     *
     * @param  \PHPStan\Node\CollectedDataNode  $node
     * @return list<StaticPropertyDeclaration>
     */
    private function declarations(CollectedDataNode $node): array
    {
        $declarations = [];

        foreach ($node->get(StaticPropertyDeclarationCollector::class) as $file => $perClass) {
            foreach ($perClass as $records) {
                foreach ($records as $record) {
                    $declarations[] = [
                        'key'  => $record['key'],
                        'name' => $record['name'],
                        'line' => $record['line'],
                        'file' => $file,
                    ];
                }
            }
        }

        return $declarations;
    }

    /**
     * Build the error for a mutated static property declaration.
     *
     * @param  StaticPropertyDeclaration  $declaration
     * @return \PHPStan\Rules\RuleError
     */
    private function error(array $declaration): RuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Static property $%s introduces mutable static state; use instance state or a constant instead.',
            $declaration['name'],
        ))
            ->identifier('sineMacula.mutableStaticProperty')
            ->file($declaration['file'])
            ->line($declaration['line'])
            ->build();
    }
}
