<?php

namespace SineMacula\Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Base test case for Sine Macula PHP_CodeSniffer sniffs.
 *
 * A concrete test is named "<SniffName>SniffTest" and lives under
 * SineMacula/Tests/<Category>/ alongside its fixtures. The sniff under test,
 * its file path and its dotted code are all derived from the test class by
 * convention, so a sniff test is just a fixture plus one assertion per scenario.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class AbstractSniffTestCase extends TestCase
{
    /**
     * Assert that the sniff reports errors on exactly the given line numbers.
     *
     * @param  string  $fixture
     * @param  array<int, int>  $expectedLines
     * @return void
     */
    protected function assertErrorsOnLines(string $fixture, array $expectedLines): void
    {
        $actualLines = array_keys($this->process($fixture)->getErrors());

        sort($actualLines);
        sort($expectedLines);

        self::assertSame($expectedLines, $actualLines);
    }

    /**
     * Run the sniff under test over a fixture in the test's own directory.
     *
     * @param  string  $fixture
     * @return \PHP_CodeSniffer\Files\LocalFile
     */
    private function process(string $fixture): LocalFile
    {
        // A built-in standard gives the Ruleset something valid to construct
        // from (it rejects an empty sniff set); its sniffs are then dropped so
        // only the sniff under test, registered directly from its file, runs.
        $config            = new Config(['--extensions=inc,php'], false);
        $config->standards = ['Generic'];

        $ruleset         = new Ruleset($config);
        $ruleset->sniffs = [];
        $ruleset->registerSniffs([$this->sniffFile()], [], []);
        $ruleset->populateTokenListeners();

        $file = new LocalFile($this->directory() . DIRECTORY_SEPARATOR . $fixture, $ruleset, $config);
        $file->process();

        return $file;
    }

    /**
     * Resolve the absolute path of the sniff under test from this test class.
     *
     * @return string
     */
    private function sniffFile(): string
    {
        return str_replace(
            [DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR, 'SniffTest.php'],
            [DIRECTORY_SEPARATOR . 'Sniffs' . DIRECTORY_SEPARATOR, 'Sniff.php'],
            (new ReflectionClass(static::class))->getFileName()
        );
    }

    /**
     * The directory holding this test class and its fixtures.
     *
     * @return string
     */
    private function directory(): string
    {
        return dirname((new ReflectionClass(static::class))->getFileName());
    }
}
