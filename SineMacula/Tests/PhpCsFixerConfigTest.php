<?php

declare(strict_types = 1);

namespace SineMacula\Tests;

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SineMacula\CodingStandards\PhpCsFixerConfig;

/**
 * Tests for the PHP CS Fixer configuration factory.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 *
 * @SuppressWarnings("php:S4833")
 * @SuppressWarnings("php:S2003")
 */
#[CoversClass(PhpCsFixerConfig::class)]
final class PhpCsFixerConfigTest extends TestCase
{
    /** @var string A minimal PHP file body for the finder fixtures. */
    private const string PHP_STUB = "<?php\n";

    /** @var string Throwaway directory holding the finder fixtures. */
    private string $directory;

    /**
     * Create a fixture directory with one matching file, one non-PHP file and
     * one dotfile so the finder's filters are observable.
     *
     * @return void
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/php-cs-fixer-config-' . bin2hex(random_bytes(6));

        mkdir($this->directory);

        file_put_contents($this->directory . '/a.php', self::PHP_STUB);
        file_put_contents($this->directory . '/b.txt', "not php\n");
        file_put_contents($this->directory . '/.hidden.php', self::PHP_STUB);

        mkdir($this->directory . '/_svn');

        file_put_contents($this->directory . '/_svn/c.php', self::PHP_STUB);
    }

    /**
     * Remove the fixture directory.
     *
     * @return void
     */
    #[\Override]
    protected function tearDown(): void
    {
        foreach (['/a.php', '/b.txt', '/.hidden.php', '/_svn/c.php'] as $file) {
            unlink($this->directory . $file);
        }

        rmdir($this->directory . '/_svn');
        rmdir($this->directory);
    }

    /**
     * The factory builds a config carrying the shared rules with overrides
     * taking precedence, caching and risky fixers enabled, and parallelism
     * detected from the environment.
     *
     * @return void
     */
    public function testBuildsSharedConfigurationWithOverrides(): void
    {
        $rules = require dirname(__DIR__, 2) . '/php/.php-cs-fixer.rules.php';

        $key       = array_key_first($rules);
        $overrides = [$key => 'overridden', 'custom_rule' => true];

        $config = PhpCsFixerConfig::make([$this->directory], $overrides);

        self::assertSame('SineMacula', $config->getName());
        self::assertTrue($config->getUsingCache());
        self::assertTrue($config->getRiskyAllowed());
        self::assertSame(array_merge($rules, $overrides), $config->getRules());
        self::assertSame(
            ParallelConfigFactory::detect()->getMaxProcesses(),
            $config->getParallelConfig()->getMaxProcesses(),
        );
    }

    /**
     * The finder yields only visible PHP files from the given directories.
     *
     * @return void
     */
    public function testFinderYieldsOnlyVisiblePhpFiles(): void
    {
        $config = PhpCsFixerConfig::make([$this->directory]);

        $files = array_map(
            static fn ($file): string => $file->getFilename(),
            iterator_to_array($config->getFinder(), false),
        );

        self::assertSame(['a.php'], $files);
    }

    /**
     * Repeated factory calls in one process keep returning the shared rules
     * rather than the boolean a stale include would produce.
     *
     * @return void
     */
    public function testRepeatedCallsKeepReturningTheSharedRules(): void
    {
        $first  = PhpCsFixerConfig::make([$this->directory]);
        $second = PhpCsFixerConfig::make([$this->directory]);

        self::assertSame($first->getRules(), $second->getRules());
        self::assertNotEmpty($second->getRules());
    }
}
