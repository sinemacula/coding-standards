<?php

declare(strict_types = 1);

namespace SineMacula\Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for Sine Macula PHP_CodeSniffer sniffs.
 *
 * A concrete test is named "<SniffName>SniffTest" and lives under
 * SineMacula/Tests/<Category>/ alongside its fixtures. The sniff under test,
 * its file path and its dotted code are all derived from the test class by
 * convention, so a test is just a fixture plus one assertion per scenario.
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

        static::assertSame($expectedLines, $actualLines);
    }

    /**
     * Assert that the sniff reports the given error codes on the given lines,
     * where the map is keyed by line and lists the short code of each error.
     *
     * @param  string  $fixture
     * @param  array<int, list<string>>  $expected
     * @return void
     */
    protected function assertErrorCodesOnLines(string $fixture, array $expected): void
    {
        $actual = [];

        foreach ($this->process($fixture)->getErrors() as $line => $columns) {
            foreach ($columns as $messages) {
                foreach ($messages as $message) {
                    $actual[$line][] = substr((string) strrchr($message['source'], '.'), 1);
                }
            }
        }

        ksort($actual);
        ksort($expected);

        static::assertSame($expected, $actual);
    }

    /**
     * Assert that the sniff reports the given rendered error messages on the
     * given lines, where the map is keyed by line and lists each message.
     *
     * @param  string  $fixture
     * @param  array<int, list<string>>  $expected
     * @return void
     */
    protected function assertErrorMessagesOnLines(string $fixture, array $expected): void
    {
        $actual = [];

        foreach ($this->process($fixture)->getErrors() as $line => $columns) {
            foreach ($columns as $messages) {
                foreach ($messages as $message) {
                    $actual[$line][] = $message['message'];
                }
            }
        }

        ksort($actual);
        ksort($expected);

        static::assertSame($expected, $actual);
    }

    /**
     * Assert that the sniff fixes the fixture to its expected `.fixed`
     * companion, and that a second pass over the output leaves it unchanged.
     *
     * @param  string  $fixture
     * @return void
     */
    protected function assertFixes(string $fixture): void
    {
        $fixed = $this->fix($this->process($fixture));

        static::assertSame(
            file_get_contents($this->directory() . DIRECTORY_SEPARATOR . $fixture . '.fixed'),
            $fixed,
            'The fixer output does not match the expected .fixed file.',
        );

        static::assertSame($fixed, $this->fix($this->processContent($fixed)), 'The fixer is not idempotent.');
    }

    /**
     * Property overrides to apply to the sniff under test (e.g. lowered
     * limits).
     *
     * @return array<string, mixed>
     */
    protected function sniffProperties(): array
    {
        return [];
    }

    /**
     * Run the sniff under test over a fixture in the test's own directory.
     *
     * @param  string  $fixture
     * @return \PHP_CodeSniffer\Files\LocalFile
     */
    private function process(string $fixture): LocalFile
    {
        $file = new LocalFile($this->directory() . DIRECTORY_SEPARATOR . $fixture, ...$this->boot());
        $file->process();

        return $file;
    }

    /**
     * Run the sniff under test over an in-memory string of code.
     *
     * @param  string  $content
     * @return \PHP_CodeSniffer\Files\DummyFile
     */
    private function processContent(string $content): DummyFile
    {
        $file = new DummyFile($content, ...$this->boot());
        $file->process();

        return $file;
    }

    /**
     * Run the fixer over an already-processed file and return its output.
     *
     * @param  \PHP_CodeSniffer\Files\File  $file
     * @return string
     */
    private function fix(File $file): string
    {
        $file->fixer->fixFile();

        return $file->fixer->getContents();
    }

    /**
     * Build the ruleset and config that run only the sniff under test.
     *
     * @return array{\PHP_CodeSniffer\Ruleset, \PHP_CodeSniffer\Config}
     */
    private function boot(): array
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

        foreach ($ruleset->sniffs as $sniff) {
            foreach ($this->sniffProperties() as $property => $value) {
                $sniff->{$property} = $value;
            }
        }

        return [$ruleset, $config];
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
            (new \ReflectionClass(static::class))->getFileName(),
        );
    }

    /**
     * The directory holding this test class and its fixtures.
     *
     * @return string
     */
    private function directory(): string
    {
        return dirname((new \ReflectionClass(static::class))->getFileName());
    }
}
