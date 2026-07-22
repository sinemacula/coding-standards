<?php

declare(strict_types = 1);

namespace SineMacula\Tests\PHPStan;

use PhpParser\Modifiers;
use PhpParser\Node\Name;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SineMacula\CodingStandards\PHPStan\Collectors\StaticPropertyDeclarationCollector;

/**
 * Tests for the static property declaration collector.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(StaticPropertyDeclarationCollector::class)]
final class StaticPropertyDeclarationCollectorTest extends TestCase
{
    /**
     * A declaration naming several static properties produces one full record
     * per name, each carrying the key, the bare name and the declaration line.
     *
     * @return void
     */
    public function testCollectsEachNameOfAMultiPropertyDeclaration(): void
    {
        $collector = new StaticPropertyDeclarationCollector;
        $class     = self::classWith([self::property(Modifiers::PUBLIC | Modifiers::STATIC, ['first', 'second'], 12)]);

        self::assertSame([
            ['key' => 'App\Support\Config::first', 'name' => 'first', 'line' => 12],
            ['key' => 'App\Support\Config::second', 'name' => 'second', 'line' => 12],
        ], $collector->processNode($class, $this->scopeForFile('/app/src/Config.php')));
    }

    /**
     * Instance property declarations are never collected, even when the class
     * carries no opt-out tag.
     *
     * @return void
     */
    public function testIgnoresInstancePropertyDeclarations(): void
    {
        $collector = new StaticPropertyDeclarationCollector;
        $class     = self::classWith([self::property(Modifiers::PUBLIC, ['value'], 12)]);

        self::assertNull($collector->processNode($class, $this->scopeForFile('/app/src/Config.php')));
    }

    /**
     * The tests-directory exemption also matches paths that use backslash
     * directory separators.
     *
     * @return void
     */
    public function testExemptsTestDirectoriesOnBackslashPaths(): void
    {
        $collector = new StaticPropertyDeclarationCollector;
        $class     = self::classWith([self::property(Modifiers::PUBLIC | Modifiers::STATIC, ['first'], 12)]);

        self::assertNull($collector->processNode($class, $this->scopeForFile('C:\app\tests\Config.php')));
    }

    /**
     * A property statement declaring the given names on the given line.
     *
     * @param  int  $flags
     * @param  array<int, string>  $names
     * @param  int  $line
     * @return \PhpParser\Node\Stmt\Property
     */
    private static function property(int $flags, array $names, int $line): Property
    {
        return new Property(
            $flags,
            array_map(static fn (string $name): PropertyItem => new PropertyItem($name), $names),
            ['startLine' => $line],
        );
    }

    /**
     * A resolved class node holding the given property statements.
     *
     * @param  array<int, \PhpParser\Node\Stmt\Property>  $properties
     * @return \PhpParser\Node\Stmt\Class_
     */
    private static function classWith(array $properties): Class_
    {
        $class = new Class_('Config', ['stmts' => $properties]);

        $class->namespacedName = new Name('App\Support\Config');

        return $class;
    }

    /**
     * A scope reporting the given path as the analysed file.
     *
     * @param  string  $file
     * @return \PHPStan\Analyser\Scope
     */
    private function scopeForFile(string $file): Scope
    {
        $scope = self::createStub(Scope::class);

        $scope->method('getFile')->willReturn($file);

        return $scope;
    }
}
