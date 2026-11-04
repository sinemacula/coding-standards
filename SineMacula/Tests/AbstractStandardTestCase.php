<?php

declare(strict_types = 1);

namespace SineMacula\Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for the standard as a whole.
 *
 * Where a sniff test isolates one sniff, this boots the real ruleset.xml with
 * every sniff it references, so a fixture is judged the way a consumer's code
 * is. That is the only way to pin how sniffs behave together: a shape can
 * satisfy each rule read on its own and still be unwritable because two of them
 * meet on the same line.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class AbstractStandardTestCase extends TestCase
{
    /**
     * Assert that the standard reports exactly the given error sources against
     * a fixture, as a map of line number to the full dotted code of each error
     * reported on it. An empty map asserts the fixture is accepted outright.
     *
     * @param  string  $fixture
     * @param  array<int, list<string>>  $expected
     * @return void
     */
    protected function assertStandardReports(string $fixture, array $expected): void
    {
        $directory = dirname($this->testFile());

        $this->assertStandardReportsFor($directory . DIRECTORY_SEPARATOR . $fixture, $expected);
    }

    /**
     * Assert the same against a file anywhere on disk, for a fixture that is
     * produced during the test rather than committed beside it.
     *
     * @param  string  $path
     * @param  array<int, list<string>>  $expected
     * @return void
     */
    protected function assertStandardReportsFor(string $path, array $expected): void
    {
        static::assertSame($this->sorted($expected), $this->sources($this->process($path)->getErrors()));
    }

    /**
     * Assert that the standard warns with exactly the given sources against a
     * fixture, as a map of line number to the full dotted code of each warning
     * on it.
     *
     * @param  string  $fixture
     * @param  array<int, list<string>>  $expected
     * @return void
     */
    protected function assertStandardWarns(string $fixture, array $expected): void
    {
        $path = dirname($this->testFile()) . DIRECTORY_SEPARATOR . $fixture;

        static::assertSame($this->sorted($expected), $this->sources($this->process($path)->getWarnings()));
    }

    /**
     * The source of each reported message, keyed by line in line order.
     *
     * @param  array<int, array<int, list<array{source: string}>>>  $reports
     * @return array<int, list<string>>
     */
    private function sources(array $reports): array
    {
        $actual = [];

        foreach ($reports as $line => $columns) {
            foreach ($columns as $messages) {
                foreach ($messages as $message) {
                    $actual[$line][] = $message['source'];
                }
            }
        }

        return $this->sorted($actual);
    }

    /**
     * The map sorted by line.
     *
     * @param  array<int, list<string>>  $map
     * @return array<int, list<string>>
     */
    private function sorted(array $map): array
    {
        ksort($map);

        return $map;
    }

    /**
     * Run the whole standard over the file at the given path.
     *
     * @param  string  $path
     * @return \PHP_CodeSniffer\Files\LocalFile
     */
    private function process(string $path): LocalFile
    {
        $config            = new Config(['--extensions=inc,php'], false);
        $config->standards = [dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ruleset.xml'];

        $file = new LocalFile($path, new Ruleset($config), $config);
        $file->process();

        return $file;
    }

    /**
     * Resolve the absolute path of the concrete test class.
     *
     * @return string
     *
     * @throws \LogicException
     */
    private function testFile(): string
    {
        $file = (new \ReflectionClass(static::class))->getFileName();

        if ($file === false) {
            throw new \LogicException(sprintf('Test class %s is not declared in a file.', static::class));
        }

        return $file;
    }
}
