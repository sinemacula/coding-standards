<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\PHPStan\Collectors;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collect runtime writes to static properties.
 *
 * Records a "Class::property" key for every assignment, compound assignment or
 * increment/decrement whose target resolves to a static property (directly or
 * through array/object access). The mutable-static rule uses these keys to flag
 * only static properties that are actually written, not merely declared.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @implements \PHPStan\Collectors\Collector<\PhpParser\Node\Expr, string>
 */
final class StaticPropertyWriteCollector implements Collector
{
    /**
     * The node type this collector inspects.
     *
     * @return string
     */
    #[\Override]
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * Return the "Class::property" key written by this node, if any.
     *
     * @param  \PhpParser\Node  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return string|null
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): ?string
    {
        $fetch = $this->writtenStaticProperty($node);

        if ($fetch === null) {
            return null;
        }

        if ($fetch->class instanceof Name === false || $fetch->name instanceof VarLikeIdentifier === false) {
            return null;
        }

        return $scope->resolveName($fetch->class) . '::' . $fetch->name->toString();
    }

    /**
     * The static property fetch written by this node, if it is a write.
     *
     * @param  \PhpParser\Node  $node
     * @return \PhpParser\Node\Expr\StaticPropertyFetch|null
     */
    private function writtenStaticProperty(Node $node): ?StaticPropertyFetch
    {
        $target = $this->assignmentTarget($node);

        return $target === null ? null : $this->staticPropertyFetch($target);
    }

    /**
     * The assigned-to expression of a write node, or null when not a write.
     *
     * @param  \PhpParser\Node  $node
     * @return \PhpParser\Node\Expr|null
     */
    private function assignmentTarget(Node $node): ?Expr
    {
        return match (true) {
            $node instanceof Assign,
            $node instanceof AssignOp,
            $node instanceof PreInc,
            $node instanceof PostInc,
            $node instanceof PreDec,
            $node instanceof PostDec => $node->var,
            default                  => null,
        };
    }

    /**
     * Unwrap array and object access to the underlying static property fetch.
     *
     * @param  \PhpParser\Node\Expr  $expr
     * @return \PhpParser\Node\Expr\StaticPropertyFetch|null
     */
    private function staticPropertyFetch(Expr $expr): ?StaticPropertyFetch
    {
        while ($expr instanceof ArrayDimFetch || $expr instanceof PropertyFetch) {
            $expr = $expr->var;
        }

        return $expr instanceof StaticPropertyFetch ? $expr : null;
    }
}
