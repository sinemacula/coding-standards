<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Disallow a redundant static return type in a final class.
 *
 * A final class cannot be extended, so late static binding has nothing to bind
 * to and `static` names exactly the class it is written in. `self` says the
 * same thing without implying an inheritance that can never happen. Both the
 * native return type and the `@return` tag are checked, including a `static`
 * nested inside a nullable, union or generic type. A method whose signature is
 * fixed by a parent class or an interface declaring `static` is left alone: PHP
 * rejects narrowing an inherited `static` to `self` outright, even where the
 * overriding class is final, so the redundancy there is not the author's to
 * remove. A trait body is skipped too - the declaration belongs to the trait,
 * which is never final, whatever the class using it is. Resolving what an
 * ancestor declares needs the whole-program type graph, which is why this is a
 * PHPStan rule rather than a single-file sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\InClassMethodNode>
 */
final class NoRedundantStaticReturnTypeRule implements Rule
{
    /** The bracket pairs a documented type may nest a further type inside. */
    private const array TYPE_BRACKETS = ['<' => '>', '(' => ')', '{' => '}', '[' => ']'];

    /**
     * The node type this rule inspects.
     *
     * @return string
     */
    #[\Override]
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * Flag a method of a final class that returns static where self would do.
     *
     * @param  \PHPStan\Node\InClassMethodNode  $node
     * @param  \PHPStan\Analyser\Scope  $scope
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if ($scope->isInTrait() || $class->isFinalByKeyword() === false) {
            return [];
        }

        $method = $node->getOriginalNode();
        $name   = $method->name->toString();

        if ($this->isSignatureLocked($class->getNativeReflection(), $name)) {
            return [];
        }

        $surfaces = [];

        if ($this->isNativeStatic($method->returnType)) {
            $surfaces[] = 'return type';
        }

        if ($this->documentsStatic($method->getDocComment()?->getText())) {
            $surfaces[] = '@return tag';
        }

        return $surfaces === []
            ? []
            : [$this->error($class->getDisplayName(), $name, implode(' and its ', $surfaces))];
    }

    /**
     * Whether an ancestor pins the method's return type to static, which PHP
     * forbids an override from narrowing to self however final the overriding
     * class is. Both the parent chain and every implemented interface are
     * consulted; `getMethod` on either resolves through its own ancestors.
     *
     * @param  \ReflectionClass<object>  $reflection
     * @param  string  $method
     * @return bool
     */
    private function isSignatureLocked(\ReflectionClass $reflection, string $method): bool
    {
        $parent = $reflection->getParentClass();

        if ($parent !== false && $this->inheritsStaticReturn($parent, $method)) {
            return true;
        }

        foreach ($reflection->getInterfaces() as $interface) {
            if ($this->inheritsStaticReturn($interface, $method)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the given ancestor declares the method with a static return type.
     *
     * @param  \ReflectionClass<object>  $reflection
     * @param  string  $method
     * @return bool
     */
    private function inheritsStaticReturn(\ReflectionClass $reflection, string $method): bool
    {
        if ($reflection->hasMethod($method) === false) {
            return false;
        }

        $inherited = $reflection->getMethod($method);

        // A private ancestor method is not inherited, so redeclaring the name
        // lower down is a fresh declaration rather than an override and PHP
        // applies no signature check to it.
        if ($inherited->isPrivate()) {
            return false;
        }

        return $this->isReflectedStatic($inherited->getReturnType());
    }

    /**
     * Whether a reflected return type is, or nests, the static type. Case is
     * not folded because reflection reports the keyword lowercased however it
     * was written.
     *
     * @param  \ReflectionType|null  $type
     * @return bool
     */
    private function isReflectedStatic(?\ReflectionType $type): bool
    {
        $members = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($members as $member) {
            if ($member instanceof \ReflectionNamedType && $member->getName() === 'static') {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the declared return type is, or nests, the static type. An
     * intersection member is skipped: PHP forbids static as part of one.
     *
     * @param  \PhpParser\Node|null  $type
     * @return bool
     */
    private function isNativeStatic(?Node $type): bool
    {
        if ($type instanceof Node\NullableType) {
            return $this->isNativeStatic($type->type);
        }

        $members = $type instanceof Node\UnionType ? $type->types : [$type];

        foreach ($members as $member) {
            if ($member instanceof Node\Name && $member->toLowerString() === 'static') {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the method's own doc comment documents a static return. Only the
     * type expression of each return tag is read, so a description that merely
     * uses the word is not mistaken for the type.
     *
     * @param  string|null  $comment
     * @return bool
     */
    private function documentsStatic(?string $comment): bool
    {
        if ($comment === null) {
            return false;
        }

        preg_match_all('/^\s*\*?\s*@(?:phpstan-|psalm-)?return\s+(\S[^\r\n]*)/mi', $comment, $matches);

        foreach ($matches[1] as $tag) {
            if (preg_match('/(?<![\w\\\])static(?![\w\\\])/i', $this->typeExpression($tag)) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Take the type expression off the front of a return tag, dropping any
     * description that follows it. Whitespace nested inside a generic, array
     * shape or parenthesised type belongs to the type and does not end it.
     *
     * @param  string  $tag
     * @return string
     */
    private function typeExpression(string $tag): string
    {
        $depth = 0;
        $type  = '';

        foreach (str_split($tag) as $character) {
            if ($depth === 0 && trim($character) === '') {
                break;
            }

            if (isset(self::TYPE_BRACKETS[$character])) {
                $depth++;
            } elseif (in_array($character, self::TYPE_BRACKETS, true)) {
                $depth--;
            }

            $type .= $character;
        }

        return $type;
    }

    /**
     * Build the error for a method returning static from a final class.
     *
     * @param  string  $class
     * @param  string  $method
     * @param  string  $surfaces
     * @return \PHPStan\Rules\RuleError
     */
    private function error(string $class, string $method, string $surfaces): RuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Method %s::%s() declares static in its %s; the class is final, so static is always self.',
            $class,
            $method,
            $surfaces,
        ))
            ->identifier('sineMacula.redundantStaticReturnType')
            ->build();
    }
}
