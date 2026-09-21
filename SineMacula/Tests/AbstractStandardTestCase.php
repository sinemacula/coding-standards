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
        $directory = dirname((new \ReflectionClass(static::class))->getFileName());

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
        $actual = [];

        foreach ($this->process($path)->getErrors() as $line => $columns) {
            foreach ($columns as $messages) {
                foreach ($messages as $message) {
                    $actual[$line][] = $message['source'];
                }
            }
        }

        ksort($actual);
        ksort($expected);

        static::assertSame($expected, $actual);
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
}
