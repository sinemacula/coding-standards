<?php

declare(strict_types = 1);

namespace SineMacula\Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;

/**
 * Base test case that runs the full ruleset, to pin how sniffs interact.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class AbstractStandardTestCase extends TestCase
{
    /**
     * Assert the full error sources reported on each line of a fixture; an
     * empty map asserts the fixture is clean.
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
     * Assert the full error sources reported on each line of a file at any
     * path.
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
     * Assert the full warning sources reported on each line of a fixture.
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
     * Map each line to the sources reported on it.
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
     * Sort the map by line.
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
