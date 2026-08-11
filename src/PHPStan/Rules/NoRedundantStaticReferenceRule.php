<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Disallow a redundant static reference in a final class.
 *
 * `new static`, `static::` and `instanceof static` all defer to a class that is
 * only decided when one is called - which, in a final class, is always the
 * class the expression is written in. Reading as though a subclass were coming
 * costs nothing today and changes meaning the day `final` is lifted, whereas
 * `self` states what actually happens. This is the expression-position
 * counterpart of the redundant static return type; the two are separate so
 * either can be silenced without the other. A trait body is skipped, since a
 * trait cannot know whether the class using it is final.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
final class NoRedundantStaticReferenceRule implements Rule
{
    /**
     * The node type this rule inspects.
     *
     * @return string
     */
    #[\Override]
    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * Flag an expression deferring to static from inside a final class.
     *
     * @param  \PhpParser\Node  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $form = $this->staticForm($node);

        if ($form === null || $scope->isInTrait()) {
            return [];
        }

        $class = $scope->getClassReflection();

        if ($class === null || $class->isFinalByKeyword() === false) {
            return [];
        }

        return [$this->error($class->getDisplayName(), $form)];
    }

    /**
     * Name the way the expression defers to static, or null where it does not.
     *
     * @param  \PhpParser\Node  $node
     * @return string|null
     */
    private function staticForm(Node $node): ?string
    {
        if ($node instanceof Node\Expr\New_) {
            return $this->isStatic($node->class) ? 'new static' : null;
        }

        if ($node instanceof Node\Expr\Instanceof_) {
            return $this->isStatic($node->class) ? 'instanceof static' : null;
        }

        return $this->accessesStatic($node) ? 'static::' : null;
    }

    /**
     * Whether the expression reaches through static for a method, a property, a
     * constant or the class name.
     *
     * @param  \PhpParser\Node  $node
     * @return bool
     */
    private function accessesStatic(Node $node): bool
    {
        if (
            $node instanceof Node\Expr\StaticCall
            || $node instanceof Node\Expr\StaticPropertyFetch
            || $node instanceof Node\Expr\ClassConstFetch
        ) {
            return $this->isStatic($node->class);
        }

        return false;
    }

    /**
     * Whether the class an expression names is the static keyword itself,
     * rather than a written name or a runtime expression.
     *
     * @param  \PhpParser\Node  $class
     * @return bool
     */
    private function isStatic(Node $class): bool
    {
        return $class instanceof Node\Name && $class->toLowerString() === 'static';
    }

    /**
     * Build the error for an expression deferring to static needlessly.
     *
     * @param  string  $class
     * @param  string  $form
     * @return \PHPStan\Rules\RuleError
     */
    private function error(string $class, string $form): RuleError
    {
        return RuleErrorBuilder::message(sprintf(
            '%s uses %s; the class is final, so static is always self.',
            $class,
            $form,
        ))
            ->identifier('sineMacula.redundantStaticReference')
            ->build();
    }
}
