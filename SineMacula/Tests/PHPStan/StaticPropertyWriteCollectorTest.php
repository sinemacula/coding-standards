<?php

declare(strict_types = 1);

namespace SineMacula\Tests\PHPStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp\Plus;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SineMacula\CodingStandards\PHPStan\Collectors\StaticPropertyWriteCollector;

/**
 * Tests for the static property write collector.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(StaticPropertyWriteCollector::class)]
final class StaticPropertyWriteCollectorTest extends TestCase
{
    /**
     * One node per write form the collector must recognise, each targeting the
     * same static property.
     *
     * @return iterable<string, array{\PhpParser\Node\Expr}>
     */
    public static function writeNodes(): iterable
    {
        yield 'assignment' => [new Assign(self::target(), new Int_(1))];

        yield 'compound assignment' => [new Plus(self::target(), new Int_(1))];

        yield 'pre-increment' => [new PreInc(self::target())];

        yield 'post-increment' => [new PostInc(self::target())];

        yield 'pre-decrement' => [new PreDec(self::target())];

        yield 'post-decrement' => [new PostDec(self::target())];
    }

    /**
     * Every write form - assignment, compound assignment, and the four
     * increment/decrement operators - yields the "Class::property" key of its
     * target.
     *
     * @param  \PhpParser\Node\Expr  $node
     * @return void
     */
    #[DataProvider('writeNodes')]
    public function testCollectsWriteKeyForEachWriteOperator(Expr $node): void
    {
        $collector = new StaticPropertyWriteCollector;

        self::assertSame('App\Sample::flag', $collector->processNode($node, $this->scope()));
    }

    /**
     * A write through nested array and object access still resolves to the
     * underlying static property.
     *
     * @return void
     */
    public function testUnwrapsArrayAndObjectAccessToTheStaticTarget(): void
    {
        $collector = new StaticPropertyWriteCollector;
        $target    = new PropertyFetch(new ArrayDimFetch(self::target(), new String_('k')), 'count');

        self::assertSame('App\Sample::flag', $collector->processNode(new Assign($target, new Int_(1)), $this->scope()));
    }

    /**
     * Expressions that read or reference values without writing to a static
     * property, all of which must be ignored.
     *
     * @return iterable<string, array{\PhpParser\Node\Expr}>
     */
    public static function nonWriteNodes(): iterable
    {
        yield 'plain variable' => [new Variable('x')];

        yield 'static property read' => [self::target()];
    }

    /**
     * Non-write expressions yield null cleanly - the collector scans every
     * expression in a program, so it must not raise diagnostics while
     * discarding them.
     *
     * @param  \PhpParser\Node\Expr  $node
     * @return void
     *
     * @SuppressWarnings("php:S112")
     */
    #[DataProvider('nonWriteNodes')]
    public function testIgnoresNonWriteExpressionsWithoutDiagnostics(Expr $node): void
    {
        $collector = new StaticPropertyWriteCollector;

        set_error_handler(static function (int $severity, string $message): never {
            throw new \ErrorException($message, 0, $severity);
        });

        try {
            $result = $collector->processNode($node, $this->scope());
        } finally {
            restore_error_handler();
        }

        self::assertNull($result);
    }

    /**
     * A fresh fetch of the static property used as the write target.
     *
     * @return \PhpParser\Node\Expr\StaticPropertyFetch
     */
    private static function target(): StaticPropertyFetch
    {
        return new StaticPropertyFetch(new Name('App\Sample'), 'flag');
    }

    /**
     * A scope that resolves class names to their literal string form.
     *
     * @return \PHPStan\Analyser\Scope
     */
    private function scope(): Scope
    {
        $scope = self::createStub(Scope::class);

        $scope->method('resolveName')->willReturnCallback(static fn (Name $name): string => $name->toString());

        return $scope;
    }
}
